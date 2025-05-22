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

```
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

- **Percentage IFF**: Percentage of items in the element marked as "Issued For Fabrication"
- **Percentage IFA**: Percentage of items in the element marked as "Issued For Approval"
- **Percentage Categorized**: Percentage of items that have been assigned to a category
- **Client Approval**: Percentage complete of "Client Approval" tasks
- **IFC Drawings Received**: Percentage complete of "IFC Drawings Received" tasks
- **Detailing IFF**: Percentage complete of "Issued For Fabrication" tasks assigned to Detailing resource

These metrics provide additional context to the timeline view, helping you understand where each element stands in the overall workflow.

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

## Legend

### Status Colors
- <span style="display:inline-block;width:12px;height:12px;background-color:#8bb9fe;margin-right:5px;"></span> **Light Blue Bar**: Not Started (0% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#0d6efd;margin-right:5px;"></span> **Blue Bar**: In Progress (1-99% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#198754;margin-right:5px;"></span> **Green Bar**: Completed (100% complete)
- <span style="display:inline-block;width:12px;height:12px;background-color:#dc3545;margin-right:5px;"></span> **Red Bar**: Late (overdue tasks)

### Timeline Markers
- <span style="display:inline-block;width:2px;height:12px;background-color:#0d6efd;margin-right:5px;"></span> **Blue Vertical Line**: Today's date
- <span style="display:inline-block;width:12px;height:12px;background-color:#dc3545;border-radius:50%;margin-right:5px;"></span> **Red Circle**: Warning indicator for late tasks

## Timeline Information

The timeline at the top shows:
- For short periods: Week numbers and date ranges with day markers
- For medium periods: Months with day counts
- For long periods: Quarters with year information

Each row represents a project breakdown element with its associated fabrication tasks.

## Controls

- **Refresh**: Update the chart with the latest data by clicking the refresh button
- **Filter Project**: Filter tasks by specific project using the dropdown menu
- **Filter Status**: Filter tasks by status (All, In Progress, Not Started, Level 1, Level 2) using the filter buttons
- **Zoom**: Adjust the zoom level to see more or less detail with the zoom buttons
- **Export**: Download the data as a CSV file for offline analysis
- **Theme**: Toggle between light and dark mode with the theme switch
- **Help**: View this help guide by clicking the help button

## Task Bars

Each bar displays:
- Task description
- Start and end dates
- Progress indicator showing completion percentage
- Warning icon for late tasks

Additional metrics like IFF and IFA percentages are displayed in the labels section.

## Getting Started

1. Use the filters to find relevant projects or tasks
2. View task details by hovering over bars to see tooltips
3. Click on a task bar to see more detailed information
4. Look for warning indicators that highlight late tasks

## Task Details Modal

When you click on a task bar, a modal opens showing:
- Task information (project, element, description, resource)
- Schedule information (start date, end date, progress, hours)
- Related tasks (other tasks in the same breakdown element)

## Tips

- Hover over any element to see detailed information in tooltips
- The current date line (blue) helps you visualize today in relation to task timelines
- Elements are organized hierarchically, with level 1 elements shown in bold
- Tasks are automatically color-coded based on status
- The chart updates when you refresh the page or click the refresh button
- Look for the IFF and IFA percentages to quickly gauge the approval status of each element