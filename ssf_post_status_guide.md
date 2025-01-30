# Grid Structures Post Fabrication Status Guide

## Overview
This grid system tracks fabricated assemblies from Final QC through to jobsite delivery. The system displays items that have either:
- Passed Final QC but haven't been fully shipped to the jobsite
- Have been shipped to galvanizer but haven't completed Final QC (these appear as warnings)
- Also, the status has to be Open TC

Each assembly follows this workflow:
1. Final QC completion
2. Ship to galvanizer
3. Return from galvanizer
4. Ship to jobsite

## Filtering Options

### Sequence Filters
- Located in the blue header bar
- Shows all active sequences with material in process
- Click "All Sequences" to remove sequence filtering
- Hover over sequence buttons to see the associated job number

### Status Filters
Located in the right side of the summary stats bar:
- **Failed QC**: Shows items with failed inspections
- **LTS (Left To Ship)**: Shows items that have passed Final QC but haven't been loaded for galvanizing
- **BLACK**: Shows items currently at galvanizer (shipped to galvanizer but not yet returned)
- **RTS (Ready To Ship)**: Shows items complete and ready for jobsite shipment
- **Reset**: Clears all filters

### Work Week Filters
- Groups items by the scheduled work week
- Disabled buttons indicate no items from that week match current filters
- Critical for tracking items that are behind schedule

## Summary Statistics
Located below the header:
- **Line Items**: Total number of unique assemblies visible in current view
- **Total Members**: Sum of all required quantities for visible items

## Grid Columns

### Basic Information
- **Job**: Job number
- **Sequence**: Fabrication sequence
- **Lot**: Lot number within sequence
- **Main Mark**: Assembly mark number
- **Weight Each**: Individual assembly weight
- **Required Qty**: Total quantity needed

### Status Columns
Each status column shows progress as "current/required [maximum possible]"

1. **Failed QC**
    - Red background if failures exist
    - Clickable to view inspection history
    - Shows quantity that failed inspection

2. **Final QC**
    - Green when complete
    - Shows fabrication completion status
    - Format: completed/required

3. **At Galv**
    - Green when complete
    - Yellow if caught up to Final QC but not complete
    - Clickable to view truck load details
    - Format: loaded/required [max possible based on Final QC]

4. **Galv Complete**
    - Green when complete
    - Yellow if caught up to loaded but not complete
    - Format: returned/required [max possible based on loaded]

5. **Jobsite**
    - Green when complete
    - Shows material delivered to jobsite
    - Format: shipped/required [max possible based on returned]

## Modal Windows

### Shipping Details Modal
Appears when clicking shipping-related cells:
- Shows detailed truck load information
- Displays:
    - Date/Time
    - Truck Number
    - Main Mark
    - Piece Mark
    - Work Package
    - Ship to Name
    - Loaded Quantity
    - Returned Quantity (for galvanizer shipments)

### QC Inspection Modal
Appears when clicking inspection-related cells:
- Shows inspection history
- Toggle to show/hide all records
- Color coding:
    - Red: Failed inspection
    - Yellow: Failed but later passed
    - Blue highlight: Currently selected record
- Links to related inspections (parent/child relationships)
- Detailed inspection data shows:
    - Test Type
    - Field being tested
    - Value recorded
    - Pass/Fail status

## Weight Totals
The weight totals shown below column headers represent the remaining potential weight that can be completed at each stage:
- Calculated only for visible/filtered items
- Updates dynamically as filters change
- Helps identify the volume of work remaining at each stage
- Particularly useful for planning galvanizing shipments and jobsite deliveries

## Special Indicators
- **Red Row Background**: Warning condition - indicates an unexpected sequence of events (e.g., material shipped to galvanizer before passing Final QC)
- **Yellow Background**: Stage is complete relative to previous stage but not to total required
- **Green Background**: Stage is complete relative to total required
- **Red Header**: Column has failed items in current view (particularly for QC failures)

## Best Practices
1. Start with sequence filter to focus on specific work area
2. Use status filters to identify what needs attention:
    - Failed QC to address quality issues
    - LTS to find material ready for galvanizing
    - BLACK to track material at galvanizer
    - RTS to plan jobsite shipments
3. Check older work weeks to find delayed material
4. Monitor weight totals to understand volume of work at each stage
5. Investigate any red row backgrounds as they indicate process violations