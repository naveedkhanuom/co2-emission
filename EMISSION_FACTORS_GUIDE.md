# Emission Factors Configuration Guide

## Overview
The manual entry module has been redesigned with three scope cards (Scope 1, Scope 2, Scope 3). Each scope has pre-defined emission sources with configurable emission factors.

## Location of Emission Factors
The emission factors are defined in JavaScript in the file:
**`resources/views/emission_records/index.blade.php`**

Look for the `emissionFactors` object around line 200-220.

## Current Emission Factors Structure

```javascript
const emissionFactors = {
    // Scope 1
    'natural-gas': { unit: 'm³', factor: 0.00196 }, // tCO2e per m³
    'diesel': { unit: 'L', factor: 0.00268 }, // tCO2e per liter
    'gasoline': { unit: 'L', factor: 0.00231 }, // tCO2e per liter
    'lpg': { unit: 'L', factor: 0.00151 }, // tCO2e per liter
    'refrigerants': { unit: 'kg', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'process-emissions': { unit: 'kg', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    
    // Scope 2
    'electricity': { unit: 'kWh', factor: 0.000527 }, // tCO2e per kWh
    'steam': { unit: 'MJ', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'heating': { unit: 'kWh', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'cooling': { unit: 'kWh', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    
    // Scope 3
    'business-travel': { unit: 'km', factor: 0.000255 }, // tCO2e per km (air travel)
    'business-travel-road': { unit: 'km', factor: 0.00015 }, // tCO2e per km (road)
    'employee-commute': { unit: 'km', factor: 0.00012 }, // tCO2e per km
    'waste': { unit: 'kg', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'purchased-goods': { unit: 'kg', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'transportation': { unit: 'km', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
    'water': { unit: 'm³', factor: 0.0 }, // ⚠️ NEEDS YOUR VALUE
};
```

## How to Add Your Emission Factor Values

1. Open `resources/views/emission_records/index.blade.php`
2. Find the `emissionFactors` object (around line 200)
3. Replace the `factor: 0.0` values with your actual emission factor values
4. Make sure the values are in **tCO₂e per unit** (tonnes of CO₂ equivalent per unit)

## Example

If you have an emission factor of 0.0005 tCO₂e per kWh for district heating, you would update:

```javascript
'heating': { unit: 'kWh', factor: 0.0005 }, // tCO2e per kWh
```

## Important Notes

- **Units**: The `unit` field defines the unit of measurement for the activity data
- **Factor Format**: All factors should be in **tCO₂e per unit** (tonnes CO₂ equivalent)
- **Precision**: Use appropriate decimal precision (typically 6-8 decimal places)
- **Calculation**: The system automatically calculates: `CO₂e = Activity Data × Emission Factor`

## Emission Sources by Scope

### Scope 1 (Direct Emissions)
- Natural Gas Combustion (m³)
- Diesel Fuel (L)
- Gasoline (L)
- LPG (L)
- Refrigerants (kg)
- Process Emissions (kg)

### Scope 2 (Indirect Emissions - Purchased Energy)
- Purchased Electricity (kWh)
- Purchased Steam (MJ)
- District Heating (kWh)
- District Cooling (kWh)

### Scope 3 (Other Indirect Emissions)
- Business Travel - Air (km)
- Business Travel - Road (km)
- Employee Commuting (km)
- Waste Disposal (kg)
- Purchased Goods & Services (kg)
- Transportation & Distribution (km)
- Water Consumption (m³)

## Testing

After updating the emission factors:
1. Click on a scope card (Scope 1, 2, or 3)
2. Select an emission source
3. Verify that the emission factor is automatically filled
4. Enter activity data
5. Verify that CO₂e is calculated correctly

## Need Help?

If you need to add new emission sources:
1. Add the option to the appropriate scope's select dropdown
2. Add the corresponding entry to the `emissionFactors` object
3. Make sure the `data-unit` and `data-factor` attributes match

