# Workweeks Module Documentation

This module allows you to view and filter production data by workweek, providing a comprehensive view of the manufacturing process from nesting to final QC.

## Data Structure

### Database Tables Used

The workweeks module aggregates data from the following tables:

- `workpackages`: Contains the main workweek groupings and package information
- `productioncontroljobs`: Contains job information
- `workshops`: Contains workshop descriptions
- `productioncontrolsequences`: Contains sequence information
- `productioncontrolitemsequences`: Maps sequences to assemblies
- `productioncontrolassemblies`: Contains assembly information
- `productioncontrolitems`: Contains individual piece information
- `shapes`: Contains shape types
- `routes`: Contains routing information
- `productioncontrolcategories`: Contains category information
- `productioncontrolitemstationsummary`: Contains station completion information
- `stations`: Contains station descriptions
- `productioncontrolitemlinks`: Contains nesting information

### Key SQL Filters

The main SQL filters used to retrieve data are:

- `wp.Group2 = :workweek`: Filters data by the selected workweek
- `wp.completed = 0`: Shows only incomplete workpackages
- `pcseq.AssemblyQuantity > 0`: Excludes empty assemblies
- `pci.MainPiece = 1`: Shows only main pieces for assemblies
- `shapes.Shape NOT IN ('HS','NU','WA','MB')`: Excludes certain shape types

## Production Process Flow

The production process follows this sequence:

1. **NESTED**: Pieces are placed on a cutlist
2. **CUT**: Pieces are cut from raw material
3. **KIT**: Cut pieces are gathered for an assembly
4. **FIT**: Pieces are fitted together
5. **WELD**: Fitted pieces are welded
6. **FINAL QC**: Final quality check before shipping

## Special Nesting Logic

The nesting stage has special logic to account for pieces that have already been cut:

- Total Pieces Needed: The total number of pieces required
- Already Cut: The number of pieces already cut
- Still Needs Nesting: Total Needed - Already Cut

This prevents double-counting pieces that are already processed.

## Filtering System

The module supports filtering by:

- Work Package
- Route
- Bay
- Category
- Sequence/Lot

Filters use an indexed approach for better performance when handling large datasets.

## Data Processing Flow

1. Basic workweek data is loaded first
2. Nested, cut, and kit data are loaded in batches to handle large datasets
3. Data is processed in chunks to avoid UI blocking
4. Station summaries are calculated and displayed

## Technical Implementation

The module is implemented using:

- PHP for server-side data retrieval
- Vanilla JavaScript for client-side functionality
- Bootstrap for styling
- Object-oriented approach for stations using class inheritance
- Memory-efficient data loading with pagination

## Performance Considerations

- Large datasets are loaded in batches (5000 records at a time)
- Data processing is done in chunks asynchronously
- Filter results are cached to avoid reprocessing
- Indexes are built for faster filtering

## Maintenance Guidelines

When modifying this module:

1. Add new station types to the `orderedStations` array
2. Update station distribution percentages in `calculateStationHours` function
3. For new piecemark stations, extend the `PiecemarkStation` class
4. For new assembly stations, extend the base `Station` class