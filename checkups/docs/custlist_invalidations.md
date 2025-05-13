# Cutlist Invalidations Documentation

## Overview

The **Cutlist Invalidations** tool is designed for the nesting department to identify and address issues with cutlist items that have been invalidated but not yet cut or completed. This documentation provides information on how to use the tool, understand the data displayed, and resolve common issues.

## Purpose

When a cutlist item is invalidated, it means that the item cannot be processed as-is and requires attention. This could be due to various reasons, such as:

- Incorrect nesting information
- Material issues
- Dimension discrepancies
- Machine compatibility problems

The Cutlist Invalidations tool makes it easy to identify these problematic items, allowing the nesting department to quickly address them and ensure smooth production flow.

## Key Features

- **Real-time data**: The tool displays current invalidated cutlist items that need attention.
- **Detailed information**: For each item, you can view comprehensive information including:
    - Cutlist ID and Item ID
    - Cutlist Description
    - Creation and Invalidation dates
    - Machine and Workshop
    - Nest Numbers
    - Material details (Shape, Grade, Dimensions)
    - Status
- **Interactive interface**: Click on any row to view detailed information about the invalidated item.
- **Search functionality**: Filter the list to find specific items quickly.
- **Sorting**: Sort by any column by clicking on the column header.
- **Export**: Export the data to CSV for further analysis or reporting.

## Understanding the Data

The main table displays the following information for each invalidated item:

- **Cutlist ID**: The unique identifier for the cutlist.
- **Item ID**: The unique identifier for the specific item within the cutlist.
- **Cutlist Description**: A description of the cutlist, usually containing project information.
- **Created Date**: When the cutlist item was created.
- **Invalidated Date**: When the cutlist item was marked as invalid.
- **Machine**: The machine designated for cutting the item.
- **Workshop**: The workshop where the cutting would take place.
- **Nest #1**: Primary nest identification number.
- **Nest #2**: Secondary nest identification number.
- **Shape**: The shape of the material to be cut.
- **Grade**: The material grade.
- **Dimension**: The dimensions of the material in imperial units.
- **Length**: The length of the item in inches.
- **Status**: The current status of the item (always "Invalidated" in this view).

## Common Issues and Resolutions

### Missing or Incorrect Nest Information

**Issue**: The Nest #1 or Nest #2 fields are empty or contain incorrect information.
**Resolution**: Re-nest the item with the correct nest information using the nesting software.

### Material Specification Issues

**Issue**: The shape, grade, or dimensions are incorrect.
**Resolution**: Update the material specifications in the system to match what is actually available.

### Machine Compatibility

**Issue**: The item is assigned to a machine that cannot process it.
**Resolution**: Reassign the item to an appropriate machine or modify the item to be compatible with the assigned machine.

## Workflow

1. **Identify**: Use the Cutlist Invalidations tool to identify items that need attention.
2. **Investigate**: Click on an item to view detailed information and determine the cause of invalidation.
3. **Resolve**: Address the issue based on the specific problem identified.
4. **Verify**: Once the issue is resolved, the item should no longer appear in the invalidations list.

## Refresh Frequency

The Cutlist Invalidations data is not automatically refreshed. To see the most up-to-date information, click the "Refresh" button at the top of the page.

## Support

If you encounter any issues with the Cutlist Invalidations tool or need assistance understanding the data, please contact the IT department or your production supervisor.