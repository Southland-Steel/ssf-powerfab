# Workweeks Module Documentation

This module allows you to view and filter production data by workweek, providing a comprehensive overview of the manufacturing process from nesting to final quality control.

## Data Sources

### Database Tables

The workweeks module aggregates data from the following database tables:

* **workpackages**: Contains the main workweek groupings (`Group2` field) and package information
* **productioncontroljobs**: Contains job information (job numbers)
* **workshops**: Contains workshop descriptions
* **productioncontrolsequences**: Contains sequence information
* **productioncontrolitemsequences**: Maps sequences to assemblies
* **productioncontrolassemblies**: Contains assembly information
* **productioncontrolitems**: Contains individual piece information
* **shapes**: Contains shape types
* **routes**: Contains routing information
* **productioncontrolcategories**: Contains category information
* **productioncontrolitemstationsummary**: Contains station completion information
* **stations**: Contains station descriptions
* **productioncontrolitemlinks**: Contains nesting information

### Main SQL Query

The core data is retrieved with this SQL query (simplified):

```sql
SELECT 
    pcj.JobNumber,
    pcseq.Description as SequenceDescription,
    pcseq.LotNumber,
    wp.WorkPackageNumber,
    wp.Group2 as WorkWeek,
    wp.Group1 as Bay,
    pci.MainMark,
    pci.PieceMark,
    shapes.Shape,
    pci.DimensionString,
    rt.Route as RouteName,
    stations.Description as StationDescription,
    pciss.QuantityCompleted,
    pciss.TotalQuantity,
    pciss.LastDateCompleted,
    pci.Length,
    pciseq.Quantity as SequenceMainMarkQuantity,
    pca.AssemblyWeightEach,
    pca.AssemblyManHoursEach,
    wp.ReleasedToFab,
    wp.OnHold,
    pccat.Description as Category
FROM workpackages as wp 
    INNER JOIN productioncontroljobs as pcj ON pcj.ProductionControlID = wp.ProductionControlID
    INNER JOIN workshops as ws ON ws.WorkshopID = wp.WorkShopID
    INNER JOIN productioncontrolsequences as pcseq ON pcseq.WorkPackageID = wp.WorkPackageID AND pcseq.AssemblyQuantity > 0
    INNER JOIN productioncontrolitemsequences as pciseq ON pciseq.SequenceID = pcseq.SequenceID AND pciseq.Quantity > 0
    INNER JOIN productioncontrolassemblies as pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
    INNER JOIN productioncontrolitems as pci ON pci.ProductionControlAssemblyID = pca.ProductionControlAssemblyID
    INNER JOIN shapes ON shapes.ShapeID = pci.ShapeID AND shapes.Shape NOT IN ('HS','NU','WA')
    LEFT JOIN routes as rt on rt.RouteID = pci.RouteID
    LEFT JOIN productioncontrolcategories as pccat on pccat.CategoryID = pci.CategoryID
    INNER JOIN productioncontrolitemstationsummary as pciss ON pciss.ProductionControlItemID = pci.ProductionControlItemID 
        AND pciss.SequenceID = pcseq.SequenceID 
        AND pciss.ProductionControlID = pcseq.ProductionControlID
    INNER JOIN stations ON stations.StationID = pciss.StationID AND stations.Description not in ('IFA','IFF','CUT','NESTED')
WHERE 
    wp.completed = 0 
    AND wp.Group2 = :workweek 
    AND pcseq.AssemblyQuantity > 0 
    AND pci.MainPiece = 1
```

Key filters:
* `wp.completed = 0`: Shows only incomplete workpackages
* `wp.Group2 = :workweek`: Filters by the selected workweek
* `pcseq.AssemblyQuantity > 0`: Excludes empty assemblies
* `pci.MainPiece = 1`: Shows only main pieces for assemblies
* `shapes.Shape NOT IN ('HS','NU','WA')`: Excludes certain shape types

## Production Process Flow

The production process follows this sequence:

1. **NESTED**: Pieces are placed on a cutlist (nesting program)
2. **CUT**: Pieces are cut from raw material
3. **KIT**: Cut pieces are gathered for an assembly
4. **PROFIT**: CNC machine processing
5. **ZEMAN**: Robotic welding
6. **FIT**: Manual fitting of pieces
7. **WELD**: Manual welding
8. **FINAL QC**: Final quality check before shipping

## Using the Filters

The module supports filtering by:

* **Work Package**: Groups of assemblies assigned to the same work package
* **Route**: Manufacturing route assigned to the assembly
* **Bay**: Physical location in the shop
* **Category**: Type of assembly (e.g., BEAMS, COLUMNS)
* **Sequence/Lot**: Sequence and lot numbers for the assembly

Click any filter button to display only the matching items. The summary statistics at the top will update to show totals for the filtered data.

## Understanding the Data

### Station Status Colors

* **Yellow**: Not started (0% complete)
* **Blue**: Partially complete
* **Green**: Complete (100%)

### Project Summary

The Project Summary section shows:
* Total line items and assemblies
* Total hours (estimated, completed, and remaining)
* Total weight (in pounds and tons)
* Productivity metrics (hours per ton and pounds per hour)

### Workweek Schedule

The Workweek Schedule table shows when each station should start working based on the selected workweek:
* NESTED starts 6 weeks before the main workweek
* CUT starts 4 weeks before
* KIT starts 3 weeks before
* FIT starts 1 week before
* WELD and FINAL QC happen during the main workweek

## Technical Implementation

The module uses:
* PHP for backend data retrieval
* Vanilla JavaScript for client-side functionality
* Bootstrap for styling
* Object-oriented approach for station calculations
* Memory-efficient data loading with pagination

Data processing occurs in chunks to prevent UI freezing when handling large datasets. Filter results are cached to improve performance.

## Troubleshooting

If the data doesn't appear correctly:
1. Try refreshing the page
2. Check that you have the correct workweek selected
3. Clear any active filters by clicking "All" buttons
4. Look for any console errors in your browser's developer tools

For detailed information about a specific assembly, click on the MainMark value to view the complete JSON data.