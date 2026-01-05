<?php

namespace App\Services;

use Carbon\Carbon;

class BillDataExtractor
{
    /**
     * Extract data from OCR text for electricity bills
     */
    public function extractElectricityData(string $text): array
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
     * Extract data from OCR text for fuel bills
     */
    public function extractFuelData(string $text): array
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

