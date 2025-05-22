# Project Schedule Gantt Chart Help Guide

This Gantt chart provides a visual timeline of project breakdown elements and their fabrication tasks, helping you track progress and manage workflows efficiently.

## Data Source

The Gantt chart visualizes data from the following database tables:
- `schedulebreakdownelements`: Contains the structure of project breakdown (up to level 2)
- `scheduletasks`: Contains task information including start/end dates and completion percentage
- `scheduledescriptions`: Contains descriptions for tasks and elements
- `projects`: Contains project information
- `resources`: Contains resource assignments
- `productioncontrolsequences`: Contains sequence information
- `productioncontrolitemsequences`: Links sequences to assemblies
- `productioncontrolassemblies`: Contains assembly information
- `productioncontrolitems`: Contains individual items
- `approvalstatuses`: Contains approval status codes and descriptions
- `workpackages`: Contains workpackage information

## Data Filtering

The chart only displays tasks with the following criteria:

```sql
WHERE  p.JobStatusID IN (1,6)
       AND sbde.Level < 3
       AND sts.PercentCompleted < 0.99
       AND resources.Description = 'Fabrication'
       AND sd.Description = 'Fabrication'
       AND sbdeval.Description IS NOT NULL
       AND sts.ActualStartDate IS NOT NULL
       AND sts.ActualEndDate IS NOT NULL
```

In more understandable terms:
1. Only **active projects** (with job status 1 or 6)
   - Status 1: Open status
   - Status 6: TC open status
2. Only showing **element levels 1 and 2** (excludes deeper nested elements)
   - Level 1: Generally represents the sequence name
   - Level 2: Generally represents the lot number
3. Only tasks that are **less than 99% complete** (filters out finished tasks)
4. Only tasks assigned to the **Fabrication** resource
5. Only tasks that have **Fabrication** as the description
6. Only elements that have a valid description
7. Only tasks that have both start and end dates defined

## Additional Data Points

The chart now includes additional metrics for each element:

- **Percentage IFF**: Percentage of items in the element marked as "Issued For Fabrication" (from Tekla Production Control PieceMarks)
- **Percentage IFA**: Percentage of items in the element marked as "Issued For Approval" (from Tekla Production Control PieceMarks)
- **Percentage Categorized**: Percentage of items that have been assigned to a category
- **Client Approval**: Percentage complete of "Client Approval" tasks (manual entry in project management)
- **IFC Drawings Received**: Percentage complete of "IFC Drawings Received" tasks
- **Detailing IFF**: Percentage complete of "Issued For Fabrication" tasks assigned to Detailing resource (from status update)

These metrics provide additional context to the timeline view, helping you understand where each element stands in the overall workflow.

## New Features

### Percentage Badges
Each task row now displays **color-coded percentage badges** positioned around the timeline:

- **Top Left**: Detailing IFF percentage (from status updates)
- **Bottom Left**: Categorized percentage (from PieceMarks with categoryID)
- **Top Right**: IFA percentage (from Tekla Production Control)
- **Bottom Right**: IFF percentage (from Tekla Production Control)
- **Labels Section**: Client Approval percentage (bottom right corner)

**Badge Colors:**
- 🔴 **Red**: Less than 5% complete
- 🟡 **Yellow**: 5% to 99% complete
- 🟢 **Green**: 99% or more complete

### Work Week Information
The system now tracks and displays work week data for each sequence/lot:

- **Work Package Numbers**: Associated work package identifiers
- **Work Week Scheduling**: Shows scheduled work weeks (format: YYWW)
- **Status Tracking**: Released to Fabrication and On Hold indicators
- **Date Ranges**: Monday-Friday date ranges for each work week

### Enhanced Tooltips
Hovering over task bars now shows comprehensive information including:
- Task description and dates
- Progress percentage and estimated hours
- IFF/IFA percentages from production control
- Client approval status
- Detailing IFF completion status

### Sequence Detail Drill-Down
**NEW**: Clicking on any task bar now navigates to a detailed sequence view showing:

#### Production Status Overview
- **Task Information Panel**: Displays job details, project manager, schedule dates, and progress
- **Summary Metrics**: Key performance indicators for the sequence/lot
- **Station Progress**: Visual progress bars for each production station

#### Detailed Assembly Breakdown
The detail page shows a comprehensive table with:
- **Assembly Information**: Category, subcategory, main mark, work package
- **Weight**: Gross assembly weight in pounds
- **Station Status**: Cut, Fit, Weld, Final QC, and Shipping progress
- **Overall Status**: Current stage in the production workflow

#### Interactive Features
- **Category Filtering**: Filter assemblies by category type
- **Completion Toggle**: Show/hide completed assemblies
- **Real-time Data**: Live updates from production control systems

## Why Some Tasks Might Not Appear

A task might not appear in the Gantt chart even if it exists in the system for several reasons:

1. **Missing Schedule Breakdown Element**: For a task to appear, it must be associated with a schedule breakdown element. If you've created a task and assigned a resource but haven't linked it to a breakdown element, it won't appear on this chart.

2. **No Fabrication Resource**: Only tasks assigned to the "Fabrication" resource are shown. Tasks assigned to other resources won't appear.

3. **Missing Dates**: Tasks must have both start and end dates defined. Tasks with undefined dates won't be displayed.

4. **Completed Tasks**: Tasks that are 99% complete or higher are considered finished and are filtered out.

5. **Deep Hierarchy**: If a task is assigned to a level 3 element or deeper, it won't appear on this chart.

6. **Inactive Projects**: Tasks associated with projects that have status codes other than 1 or 6 won't be displayed.

If you're expecting to see a task in the chart but it's not appearing, check that it meets all of the criteria listed above.

## How Data Is Structured

The chart uses a hierarchical breakdown of data:

1. **Projects** (Level 0): The top level representing a job, identified by JobNumber
2. **Sequence Breakdown** (Level 1): Main breakdown components of a project, typically representing sequence names
3. **LotNumber Breakdown** (Level 2): Further divisions of main elements, typically representing lot numbers
4. **Tasks**: Specific fabrication tasks assigned to elements

The element path is constructed as follows:
- For Level 1 elements: `JobNumber.ElementDescription`
- For Level 2 elements: `JobNumber.ParentElementDescription.ElementDescription`

This creates a unique identifier for each element in the chart, helping you track the relationship between tasks and their parent elements.

## Pure Chronological Organization

The Gantt chart is organized in a **strictly chronological order**:

- Each task appears in the exact order it will occur in time
- Tasks are sorted by start date, with earliest tasks at the top
- When multiple tasks start on the same date, they are sorted by end date (shortest duration first)

This means that tasks from different projects and elements will be mixed together based solely on their timing. You'll see the exact sequence of work over time, regardless of which project or element a task belongs to.

For easier identification, each task shows its project code in a highlighted box.

## Enhanced Data Aggregation

### Complex Query Structure
The system uses a sophisticated multi-CTE (Common Table Expression) SQL query that:

1. **ActiveFabricationProjects CTE**: Identifies all active fabrication tasks meeting the base criteria
2. **ResourceTaskCombinations CTE**: Defines tracked resource/task combinations (Client Approval, IFC Drawings, Detailing IFF)
3. **ResourceTaskPercentages CTE**: Calculates completion percentages for each resource/task combination
4. **SequenceLevelSummary & LotLevelSummary CTEs**: Aggregate production control data by sequence and lot levels
5. **WorkWeekInfo CTE**: Extracts work package and scheduling information
6. **CombinedResults CTE**: Merges all data sources into final result set

### Key Joins and Relationships
The query establishes relationships between:
- Schedule breakdown elements and their tasks
- Production control sequences and item sequences
- Approval statuses and production control items
- Work packages and scheduling information
- Resource assignments and task descriptions

## Legend

### Status Colors
- <span style="display:inline-block;width:12px;height:12px;background-color:#a3c6fd;margin-right:5px;"></span> **Light Blue Bar**: Not Started (0% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#0d21fd;margin-right:5px;"></span> **Blue Bar**: In Progress (1-99% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#198754;margin-right:5px;"></span> **Green Bar**: Completed (100% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#dc3545;margin-right:5px;"></span> **Red Bar**: Late (overdue tasks)

### Timeline Markers
- <span style="display:inline-block;width:2px;height:12px;background-color:#0d6efd;margin-right:5px;"></span> **Blue Vertical Line**: Today's date
- <span style="display:inline-block;width:12px;height:12px;background-color:#dc3545;border-radius:50%;margin-right:5px;"></span> **Red Circle**: Warning indicator for late tasks

### Badge Legend
- 🔴 **Red Badge**: Critical status (< 5% complete)
- 🟡 **Yellow Badge**: In progress (5-99% complete)
- 🟢 **Green Badge**: Complete (≥ 99% complete)

## Timeline Information

The timeline at the top shows:
- For short periods: Week numbers and date ranges with day markers
- For medium periods: Months with day counts
- For long periods: Quarters with year information

Each row represents a project breakdown element with its associated fabrication tasks.

## Controls and Navigation

### Main Gantt Chart Controls
- **Refresh**: Update the chart with the latest data by clicking the refresh button
- **Filter Project**: Filter tasks by specific project using the dropdown menu
- **Filter Status**: Filter tasks by status using the filter buttons:
   - All Tasks
   - In Progress
   - Not Started
   - Late
   - Client Approved
- **Theme Toggle**: Switch between light and dark mode with the theme switch
- **Help**: View this help guide by clicking the help button

### Filter Buttons
- **All Tasks**: Show all tasks meeting the base criteria
- **In Progress**: Show only tasks with 1-99% completion
- **Not Started**: Show only tasks with 0% completion
- **Late**: Show only overdue tasks
- **Client Approved**: Show only tasks where client approval is 99%+ complete

### Sequence Detail Page Controls
- **Category Filters**: Filter assemblies by category (All Categories, plus specific categories)
- **Show/Hide Completed**: Toggle visibility of completed assemblies
- **Back to Gantt Chart**: Return to the main timeline view

## Task Bars and Visual Elements

Each task bar displays:
- **Task Description**: Primary task information
- **Project Code**: Highlighted project identifier
- **Progress Indicator**: Visual completion percentage
- **Warning Icons**: Late task indicators
- **Percentage Badges**: Positioned metrics around the timeline

## Getting Started

1. **Overview**: Start by viewing the chronological timeline to understand the sequence of work
2. **Filtering**: Use project and status filters to focus on specific areas of interest
3. **Details**: Click on any task bar to drill down into detailed assembly information
4. **Badges**: Review the color-coded percentage badges to quickly assess approval and completion status
5. **Navigation**: Use the back button in detail views to return to the main timeline

## Sequence Detail Page

### Data Source for Detail View
The sequence detail page pulls data from:
```sql
FROM productioncontroljobs pcj 
INNER JOIN productioncontrolsequences pcseq ON pcseq.ProductionControlID = pcj.ProductionControlID
LEFT JOIN workpackages wp ON wp.WorkPackageID = pcseq.WorkPackageID
INNER JOIN productioncontrolitemsequences pciseq ON pciseq.SequenceID = pcseq.SequenceID
INNER JOIN productioncontrolassemblies pca ON pca.ProductionControlAssemblyID = pciseq.ProductionControlAssemblyID
INNER JOIN productioncontrolitems pcia ON pcia.ProductionControlItemID = pca.MainPieceProductionControlItemID
LEFT JOIN productioncontrolitemstationsummary pciss ON pciss.ProductionControlItemID = pca.MainPieceProductionControlItemID
LEFT JOIN stations ON stations.StationID = pciss.StationID
WHERE pcj.JobNumber = :jobNumber 
AND pcseq.AssemblyQuantity > 0
AND REPLACE(pcseq.Description, CHAR(1), '') = :sequenceName
```

### Station Tracking
The detail view tracks progress through production stations:
- **CUT**: Material cutting operations
- **FIT**: Assembly fitting operations
- **WELD**: Welding operations
- **FINAL QC**: Quality control inspection
- **SHIPPING**: Final shipping preparation (if applicable)

### Status Determination
Assembly status is determined hierarchically:
1. **Shipped**: If shipping station is ≥98% complete
2. **QC Complete**: If final QC station is ≥98% complete
3. **Welded**: If weld station is ≥98% complete
4. **Fitted**: If fit station is ≥98% complete
5. **Cut**: If cut station is ≥98% complete
6. **In Progress**: If any station has > 0% completion
7. **Not Started**: If all stations are at 0% completion

## Tips and Best Practices

- **Badge Interpretation**: Use the percentage badges to quickly identify bottlenecks in approval processes
- **Chronological View**: The timeline shows the actual sequence of work, helping with resource planning
- **Detail Drilling**: Click through to sequence details to understand specific assembly issues
- **Filter Combinations**: Combine project and status filters to focus on critical items
- **Theme Selection**: Choose the theme that works best for your display environment
- **Regular Refresh**: Click refresh to ensure you're viewing the most current data
- **Tooltip Usage**: Hover over elements for detailed information without navigating away from the main view

## Troubleshooting

### Common Issues
1. **Missing Tasks**: Verify tasks meet all filtering criteria (active project, fabrication resource, dates defined, <99% complete)
2. **Empty Detail Views**: Ensure the sequence has associated production control data
3. **Percentage Discrepancies**: Badge percentages come from different data sources (schedule vs. production control)
4. **Date Range Issues**: Timeline automatically adjusts to show all visible tasks with appropriate padding

### Performance Considerations
- The system uses complex queries with multiple CTEs for comprehensive data aggregation
- Large date ranges or many active projects may increase load times
- Use project filters to improve performance when focusing on specific work