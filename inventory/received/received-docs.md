## Overview
This documentation explains the Inventory Movement Data tracking system, which monitors inventory transactions including receipts, purchase orders, and take-from-stock transactions. The system is designed to help accountants and inventory managers track material movement and valuation across the organization.

## Data Sources
The system pulls data from two main sources and merges three types of transactions:

1. **Inventory Table (inv)**
    - Contains "REC" transactions: Items received into inventory
    - Contains "PO" transactions: Items that are on order with due dates falling within the selected period
    - Source field will show as "inv"

2. **Inventory History Table (ihist)**
    - Contains "TFS" transactions: Items taken from stock
    - Source field will show as "ihist"

## Transaction Types
The system uses three transaction types to categorize inventory movements:

- **REC (Received)**: Items that have been received into inventory
- **PO (Purchase Order)**: Items that are on order but not yet received
- **TFS (Take From Stock)**: Items that have been taken from inventory

## Key Fields Explained

### Identification Fields
- **ItemID**: Unique identifier from the respective source table (inv or ihist)
- **TrueShapeID**: Reference to associated CNC file
- **DimensionString**: Profile dimensions of the material

### Physical Properties
- **Shape**: Physical form of the material
- **Grade**: Material grade or specification
- **Dimension**: Dimensions in imperial units
- **Length**: Length in inches
- **Weight**: Weight in pounds

### Job Information
- **Job**: Current job assignment
- **JobReserve**: Current reservation for future use
- **PreviousJob**: Prior job assignment
- **OriginalJob**: First job assignment

### Financial Information
- **Valuation**: Total dollar value for the line item (not per unit)

### Purchase Information
- **PONumber**: Purchase order reference
- **Supplier**: Vendor name
- **HeatNo**: Material heat number for traceability
- **BillOfLadingNo**: Shipping document reference

### TFS Information
- **TFSDate**: Date when item was taken from stock (if applicable)
- **TFSJob**: Job number associated with the take from stock transaction

## Using the Interface

### Date Selection
- Use the date range picker to select specific periods
- Quick select buttons available for common ranges:
    - Today
    - Yesterday
    - This Week
    - This Month
    - Last Month

### Filtering Options
1. **Shapes Filter**: Filter by material shapes
2. **PO Numbers Filter**: Filter by purchase orders
3. **Vendors Filter**: Filter by suppliers
4. **Transaction Types**: Filter by movement type (REC, PO, TFS)

### Summary Statistics
The interface shows three key metrics:
1. **Total Line Items**: Number of transactions in the selected period
2. **Summed Quantity**: Summed quantity of all transactions
3. **Total Valuation**: Summed value of all transactions

### Data Export
- Use the "Export CSV" button to download the filtered data
- Exports include all visible columns and respect current filters

### Detailed View
- Click any row to see complete item details
- Modal window shows all available information for the selected item
- Fields with null or empty values are automatically hidden

## Important Notes for Accountants

1. **Valuation Calculations**
    - Positive values indicate incoming inventory (REC, PO)
    - Negative values indicate outgoing inventory (TFS)
    - Valuation represents the total value for the entire line item

2. **Reconciliation Tips**
    - Use date ranges that match your accounting periods
    - Export to CSV for detailed analysis in Excel
    - Cross-reference PONumbers with accounting system
    - Pay attention to the source field (inv vs ihist) when tracking items