# Emission factor reconciliation

Built-in catalogue entries (priceable): **244**
Database factor rows:                  **213**
Matched on source + unit:              **6**
  agree within 1.0%:                   **6**
  DISAGREE:                            **0**
Only in the built-in catalogue:        **238**
Only in the database:                  **207**
Scope 2 grid sources (region-keyed, not comparable): **13**
Database pairs with only region-specific rows (no generic baseline): **0**

> Comparison is against each source's GENERIC row (default / Global / no region).
> Country-specific rows are supposed to differ and are not treated as conflicts.

## Conflicts — each needs an accounting decision

_None._

## Present only in the built-in catalogue

_238 entries. These become new rows when the catalogue is compiled in._

| Scope | Source | Unit | Factor |
|---|---|---|---:|
| 1 | Agricultural Byproducts | kg | 0.00002204 |
| 1 | Agricultural Byproducts | tonnes | 0.02204 |
| 1 | Anthracite Coal | kg | 0.0026132757 |
| 1 | Anthracite Coal | tonnes | 2.61327575 |
| 1 | Biodiesel (B100) | liters | 0.0000081891 |
| 1 | Biodiesel (B100) | gallons | 0.000031104 |
| 1 | Bioethanol (E100) | liters | 0.0000051759 |
| 1 | Bioethanol (E100) | gallons | 0.0000196101 |
| 1 | Biogas | m3 | 0.0000011009 |
| 1 | Biogas | kWh | 0.0000001962 |
| 1 | Biomass Combustion | tonnes | 0.02964 |
| 1 | Bituminous Coal | kg | 0.0023354673 |
| 1 | Bituminous Coal | tonnes | 2.3354673 |
| 1 | Blast Furnace Gas | m3 | 0.0006421346 |
| 1 | Blast Furnace Gas | kWh | 0.0009361962 |
| 1 | Butane | liters | 0.0017635206 |
| 1 | Butane | gallons | 0.006675777 |
| 1 | Coal Combustion | tonnes | 2.43110555 |
| 1 | Coke Oven Gas | m3 | 0.0017201092 |
| 1 | Coke Oven Gas | kWh | 0.0001601962 |
| 1 | Crude Oil | liters | 0.0030601088 |
| 1 | Crude Oil | gallons | 0.011578151 |
| 1 | Diesel (Stationary) | liters | 0.0026847237 |
| 1 | Diesel (Stationary) | gallons | 0.010163048 |
| 1 | Ethane | liters | 0.0010759538 |
| 1 | Ethane | gallons | 0.0040736025 |
| 1 | Fuel Oil (Heating Oil) | liters | 0.0029694284 |
| 1 | Fuel Oil (Heating Oil) | gallons | 0.011245721 |
| 1 | Fuel Oil No. 2 | liters | 0.0026847237 |
| 1 | Fuel Oil No. 2 | gallons | 0.010243048 |
| 1 | Fuel Oil No. 4 | liters | 0.0029040882 |
| 1 | Fuel Oil No. 4 | gallons | 0.010994506 |
| 1 | Fuel Oil No. 6 (Residual) | liters | 0.0029863555 |
| 1 | Fuel Oil No. 6 (Residual) | gallons | 0.011305478 |
| 1 | Gasoline (Stationary) | liters | 0.0023228489 |
| 1 | Gasoline (Stationary) | gallons | 0.008793646 |
| 1 | Jet Fuel (Stationary) | liters | 0.0025424807 |
| 1 | Jet Fuel (Stationary) | gallons | 0.009625076 |
| 1 | Kerosene | liters | 0.0025464807 |
| 1 | Kerosene | gallons | 0.009639076 |
| … | _198 more not listed_ | | |

## Present only in the database

_207 rows. Each is either a factor the catalogue lacks, or the same factor under a different name or unit spelling._

---

Read only — nothing was changed. Resolving a conflict restates every historical
record priced at the losing value, so each decision needs recording alongside
the figure it changes.
