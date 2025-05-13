# Cut List View User Guide

## Overview
The Cut List View page is a comprehensive tool for managing and tracking items that need to be cut. This interface allows users to view, filter, and analyze cutting requirements across different work weeks, machines, and materials.

## Work Week Selection
- At the top of the page, you'll find work week buttons displayed in the format "YYWW" (Year-Week)
- The current work week is highlighted
- Click any work week to load its cutting requirements

## Filtering Options
The page provides several filtering options to help narrow down the displayed items:

### Available Filters
1. **Group**: Machine group classification
2. **Machine**: Specific cutting machine
3. **Shape**: Material profile type
    - C: Channel
    - L: Angle
    - PL: Plate
    - And others as applicable
4. **Grade**: Material grade specification
5. **Dimension**: Material dimensions
6. **LOC**: Storage location of the material

Each filter section shows the number of available options in brackets, and you can select multiple options within each category.

## Main Data Display

### Summary Statistics
At the top of the data table, you'll find key metrics:
- Total line items
- Total weight (this does not include drop, it just sums up the weight of the pieces)
- Total pieces to cut
- Number of unique jobs displayed
- Number of work packages displayed

### Detailed Table Columns
1. **Nest Number**: Steel Projects Nest Number
2. **Barcode**: Tekla Bar-Code
3. **Shape**: Material profile
4. **Dimension**: Material dimension
5. **Length**: Stock length in feet and inches
6. **Grade**: Material grade
7. **Location**: Storage location (clickable to view serial numbers)
8. **Job Number**: Associated job identifier
9. **Sequence**: Production sequence
10. **Work Package**: Work package identifier
11. **Weight**: Material weight in pounds
12. **Group**: Machine group assignment
13. **Machine**: Assigned cutting machine
14. **Cutlist**: Cut list name/identifier

## Interactive Features

### Expanding Row Details
- Click any row to expand and view detailed information about the pieces to be cut from that stock material
- Expanded details show:
    - MainMark
    - PieceMark
    - Width
    - Length
    - Quantity
    - WorkWeek
    - Sequence
    - Category

### Location Information
- Click on any location link to view associated serial numbers
- The popup will show:
    - Current location
    - All serial numbers associated with the material

## Detailed Summary View
Click the "View Detailed Summary" button to see comprehensive breakdowns by:
- Machine utilization
- Sequence distribution
- Work package statistics

Each breakdown includes:
- Number of line items
- Total pieces
- Total weight

## Tips for Effective Use
1. Use filters in combination to quickly find specific materials or jobs
2. Check the summary statistics to understand the current workload
3. Expand rows to verify cutting requirements for each stock material
4. Use the location links to confirm material availability
5. Reference the detailed summary view for production planning

This interface serves as a central tool for production planning, material tracking, and cut list management in the manufacturing process.