<?php

namespace App\Services;

use App\Services\AI\ClaudeService;
use Carbon\Carbon;

class BillDataExtractor
{
    protected ClaudeService $claude;

    public function __construct(ClaudeService $claude)
    {
        $this->claude = $claude;
    }

    /**
     * Extract data from OCR text for electricity bills.
     *
     * Strategy: build a regex baseline, then (if Claude is configured) overlay
     * Claude's structured extraction on top. Claude is far more robust to
     * arbitrary bill layouts; regex remains the always-available fallback.
     */
    public function extractElectricityData(string $text): array
    {
        $regex = $this->extractElectricityDataViaRegex($text);

        return $this->overlayWithClaude($text, 'electricity', $regex);
    }

    /**
     * Extract data from OCR text for fuel bills (Claude-first, regex fallback).
     */
    public function extractFuelData(string $text): array
    {
        $regex = $this->extractFuelDataViaRegex($text);

        return $this->overlayWithClaude($text, 'fuel', $regex);
    }

    /**
     * Ask Claude to extract the bill fields and overlay any non-null values on
     * top of the regex baseline. Returns the regex baseline unchanged if Claude
     * is disabled or fails, so callers always get a usable result.
     */
    protected function overlayWithClaude(string $text, string $billType, array $regex): array
    {
        if (!$this->claude->enabled() || trim($text) === '') {
            $regex['extraction_method'] = 'regex';
            return $regex;
        }

        $unit = $billType === 'fuel' ? 'L (litres; convert gallons to litres at 3.78541)' : 'kWh';

        $system = 'You extract structured data from noisy OCR text of utility/fuel bills. '
            . 'Values may be misaligned or contain OCR errors; infer carefully and never fabricate.';

        $prompt = "Bill type: {$billType}.\n"
            . "Extract these fields from the OCR text below and return JSON with exactly these keys:\n"
            . "- bill_date: the bill/invoice/purchase date as \"YYYY-MM-DD\", or null\n"
            . "- consumption: total {$billType} consumed as a number ({$unit}), or null\n"
            . "- consumption_unit: \"" . ($billType === 'fuel' ? 'L' : 'kWh') . "\"\n"
            . "- cost: total amount due as a number (digits only, no currency symbol), or null\n"
            . "- supplier_name: the utility/supplier/station name, or null\n"
            . "- confidence: \"low\", \"medium\", or \"high\" reflecting how certain you are\n\n"
            . "OCR TEXT:\n\"\"\"\n{$text}\n\"\"\"";

        $result = $this->claude->json($prompt, $system, ['temperature' => 0]);

        if (!is_array($result)) {
            $regex['extraction_method'] = 'regex';
            return $regex;
        }

        $merged = $regex;

        // Same sanity ceilings the regex baseline uses, so a hallucinated/huge
        // Claude number can't override a reasonable regex value.
        $maxConsumption = $billType === 'fuel' ? 100000 : 1000000;
        $maxCost = $billType === 'fuel' ? 100000 : 1000000;

        // Overlay each field only when Claude returned a usable value.
        if (!empty($result['bill_date'])) {
            $parsed = $this->parseDate((string) $result['bill_date']);
            if ($parsed) {
                $merged['bill_date'] = $parsed;
            }
        }

        if (isset($result['consumption']) && is_numeric($result['consumption'])) {
            $consumption = (float) $result['consumption'];
            if ($consumption > 0 && $consumption < $maxConsumption) {
                $merged['consumption'] = $consumption;
            }
        }

        if (!empty($result['consumption_unit'])) {
            $merged['consumption_unit'] = (string) $result['consumption_unit'];
        }

        if (isset($result['cost']) && is_numeric($result['cost'])) {
            $cost = (float) $result['cost'];
            if ($cost > 0 && $cost < $maxCost) {
                $merged['cost'] = $cost;
            }
        }

        if (!empty($result['supplier_name'])) {
            $merged['supplier_name'] = trim((string) $result['supplier_name']);
        }

        if (!empty($result['confidence']) && in_array($result['confidence'], ['low', 'medium', 'high'], true)) {
            $merged['confidence'] = $result['confidence'];
        }

        $merged['extraction_method'] = 'claude';

        return $merged;
    }

    /**
     * Extract data from OCR text for electricity bills (regex baseline).
     */
    public function extractElectricityDataViaRegex(string $text): array
    {
        $data = [
            'bill_date' => null,
            'consumption' => null,
            'consumption_unit' => 'kWh',
            'cost' => null,
            'supplier_name' => null,
            'confidence' => 'low'
        ];

        // Extract dates (multiple patterns)
        $datePatterns = [
            '/bill\s+date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/issued?\s+on[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $date = $this->parseDate($matches[1]);
                    if ($date) {
                        $data['bill_date'] = $date;
                        $data['confidence'] = 'medium';
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Extract consumption (kWh)
        $consumptionPatterns = [
            '/consumption[:\s]+([\d,]+\.?\d*)\s*kwh/i',
            '/total\s+consumption[:\s]+([\d,]+\.?\d*)\s*kwh/i',
            '/usage[:\s]+([\d,]+\.?\d*)\s*kwh/i',
            '/([\d,]+\.?\d*)\s*kwh/i',
            '/kwh[:\s]+([\d,]+\.?\d*)/i',
        ];

        foreach ($consumptionPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $consumption = (float) str_replace(',', '', $matches[1]);
                if ($consumption > 0 && $consumption < 1000000) { // Sanity check
                    $data['consumption'] = $consumption;
                    $data['confidence'] = 'high';
                    break;
                }
            }
        }

        // Extract cost/amount
        $costPatterns = [
            '/total[:\s]+([\d,]+\.?\d*)/i',
            '/amount[:\s]+([\d,]+\.?\d*)/i',
            '/due[:\s]+([\d,]+\.?\d*)/i',
            '/aed[:\s]+([\d,]+\.?\d*)/i',
            '/usd[:\s]+([\d,]+\.?\d*)/i',
            '/\$([\d,]+\.?\d*)/',
            '/total\s+amount[:\s]+([\d,]+\.?\d*)/i',
        ];

        foreach ($costPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cost = (float) str_replace(',', '', $matches[1]);
                if ($cost > 0 && $cost < 1000000) { // Sanity check
                    $data['cost'] = $cost;
                    break;
                }
            }
        }

        // Extract supplier name
        $supplierPatterns = [
            '/supplier[:\s]+([a-z\s]+)/i',
            '/utility[:\s]+([a-z\s]+)/i',
            '/company[:\s]+([a-z\s]+)/i',
        ];

        foreach ($supplierPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['supplier_name'] = trim($matches[1]);
                break;
            }
        }

        return $data;
    }

    /**
     * Extract data from OCR text for fuel bills (regex baseline).
     */
    public function extractFuelDataViaRegex(string $text): array
    {
        $data = [
            'bill_date' => null,
            'consumption' => null,
            'consumption_unit' => 'L',
            'cost' => null,
            'supplier_name' => null,
            'confidence' => 'low'
        ];

        // Extract dates
        $datePatterns = [
            '/date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/purchase\s+date[:\s]+(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/i',
            '/(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})/',
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $date = $this->parseDate($matches[1]);
                    if ($date) {
                        $data['bill_date'] = $date;
                        $data['confidence'] = 'medium';
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Extract fuel quantity (liters or gallons)
        $quantityPatterns = [
            '/quantity[:\s]+([\d,]+\.?\d*)\s*(l|liters?|gal|gallons?)/i',
            '/volume[:\s]+([\d,]+\.?\d*)\s*(l|liters?|gal|gallons?)/i',
            '/([\d,]+\.?\d*)\s*(l|liters?|gal|gallons?)/i',
            '/fuel[:\s]+([\d,]+\.?\d*)\s*(l|liters?|gal|gallons?)/i',
        ];

        foreach ($quantityPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $quantity = (float) str_replace(',', '', $matches[1]);
                $unit = strtolower($matches[2]);
                
                // Convert gallons to liters if needed
                if (stripos($unit, 'gal') !== false) {
                    $quantity = $quantity * 3.78541; // Convert to liters
                }
                
                if ($quantity > 0 && $quantity < 100000) { // Sanity check
                    $data['consumption'] = $quantity;
                    $data['consumption_unit'] = 'L';
                    $data['confidence'] = 'high';
                    break;
                }
            }
        }

        // Extract cost
        $costPatterns = [
            '/total[:\s]+([\d,]+\.?\d*)/i',
            '/amount[:\s]+([\d,]+\.?\d*)/i',
            '/cost[:\s]+([\d,]+\.?\d*)/i',
            '/aed[:\s]+([\d,]+\.?\d*)/i',
            '/usd[:\s]+([\d,]+\.?\d*)/i',
            '/\$([\d,]+\.?\d*)/',
        ];

        foreach ($costPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $cost = (float) str_replace(',', '', $matches[1]);
                if ($cost > 0 && $cost < 100000) {
                    $data['cost'] = $cost;
                    break;
                }
            }
        }

        // Extract supplier name
        $supplierPatterns = [
            '/supplier[:\s]+([a-z\s]+)/i',
            '/station[:\s]+([a-z\s]+)/i',
            '/company[:\s]+([a-z\s]+)/i',
        ];

        foreach ($supplierPatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $data['supplier_name'] = trim($matches[1]);
                break;
            }
        }

        return $data;
    }

    /**
     * Parse date string to Y-m-d format
     */
    private function parseDate(string $dateString): ?string
    {
        $dateString = trim($dateString);
        
        // Try different date formats
        $formats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];
        
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, $dateString);
                if ($date && $date->year > 2000 && $date->year < 2100) {
                    return $date->format('Y-m-d');
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try Carbon's flexible parser
        try {
            $date = Carbon::parse($dateString);
            if ($date && $date->year > 2000 && $date->year < 2100) {
                return $date->format('Y-m-d');
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }
}

