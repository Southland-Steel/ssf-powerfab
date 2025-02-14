# Project Manager's Guide to Timeline and Sequence Status Views

## Overview
This guide covers two interconnected views designed to help project managers track fabrication progress:
1. Timeline View
2. Sequence Status View

## Timeline View

### Purpose
The Timeline view provides a Gantt-style visualization of your project sequences, showing:
- Fabrication schedules
- Work package timing
- Key milestones (IFF, NSI)
- Categorization status
- Resource allocation through weekly hours

### Key Features

#### Main Timeline Display
- Click on Job: Sequence Title to drill down sequence status
- Each row represents a project sequence
- Blue/Velvet bars indicate fabrication start-end date from fabrication timeline
- Blue background indicates sequence hours (darker means more hours)
- Fab completion percentage is shown within each bar
- Today's date and 8-week horizon are marked with vertical lines

#### Work Package Integration
- Bracket indicators show work package start/end dates
- Color coding indicates work package status:
    - Green: Released
    - Yellow: On Hold
    - Gray: Not Released

#### Filtering and Views
- Filter by project number
- "View Weekly Hours" shows resource loading
- Export functionality for data analysis

#### Status Indicators
- Circle - IFF (Issued For Fabrication) percentage
- Diamong - NSI (Material Status) percentage
- Categorization status shown through row highlighting
- Striped background indicates incomplete IFF status

## Sequence Status View

### Purpose
Accessed by clicking a sequence in the Timeline view, this detailed view shows:
- Assembly-level fabrication progress
- Station-by-station completion status
- Category and subcategory breakdowns

### Key Features of sequence status

#### Progress Tracking
Shows completion percentages across stations:
1. Nested
2. Cut
3. Fit
4. Weld
5. Final QC
6. Shipping

#### Status Color Coding
- Green: ≥90% complete
- Yellow: 50-89% complete
- Red: <50% complete

#### Filtering
- Filter by category
- Automatic sorting puts incomplete items at top
- Completed assemblies move to bottom

### Additional Information
- Gross weight per assembly
- Lot numbers
- Work package numbers
- Main marks

## Best Practices

### Daily Usage
1. Start with Timeline View for high-level project status
2. Click into Sequence Status for detailed progress
3. Check categorization status early - this affects downstream work
4. Monitor IFF completion - incomplete IFF may delay fabrication
5. Use weekly hours view for resource planning

### Common Workflows

#### Tracking New Sequences
1. Ensure IFF is complete
2. Check categorization status in Timeline View
3. Monitor work package status
4. Click through to Sequence Status for detailed setup progress

#### Monitoring Active Fabrication
1. Use Timeline View to identify active sequences
2. Check work package alignment with schedule
3. Use Sequence Status to verify station-by-station progress
4. Monitor shipping status for completed assemblies

#### Resource Planning
1. Use "View Weekly Hours" to check loading
2. Look for peaks and valleys in resource allocation
3. Compare work package timing with fabrication bars
4. Export data for detailed analysis if needed

## Troubleshooting

### Common Issues
1. Missing Timeline Bars
    - Check if sequence is properly scheduled
    - Verify IFF status
    - Confirm work packages are assigned

2. Incomplete Status View
    - Verify categorization is complete
    - Check for missing station assignments
    - Confirm quantities match work packages

## Regular Monitoring
Review these key metrics daily:
1. Categorization status for upcoming work
2. IFF completion for near-term sequences
3. Purchasing of non-stock items
4. Work package alignment with schedule
5. Station-by-station progress
6. Resource loading for next 8 weeks

Use both views together for effective project management and early issue identification.