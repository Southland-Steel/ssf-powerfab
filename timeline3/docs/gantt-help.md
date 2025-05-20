## Pure Chronological Organization

The Gantt chart is organized in a **strictly chronological order**:

- Each task appears in the exact order it will occur in time
- Tasks are sorted by start date, with earliest tasks at the top
- When multiple tasks start on the same date, they are sorted by end date (shortest duration first)

This means that tasks from different projects and elements will be mixed together based solely on their timing. You'll see the exact sequence of work over time, regardless of which project or element a task belongs to.

For easier identification, each task shows its project code in a highlighted box.# Project Schedule Gantt Chart Help Guide

This Gantt chart provides a visual timeline of project breakdown elements and their fabrication tasks, helping you track progress and manage workflows efficiently.

## Data Source

The Gantt chart visualizes data from the following database tables:
- `schedulebreakdownelements`: Contains the structure of project breakdown (up to level 2)
- `scheduletasks`: Contains task information including start/end dates and completion percentage
- `scheduledescriptions`: Contains descriptions for tasks and elements
- `projects`: Contains project information
- `resources`: Contains resource assignments

The chart only displays tasks with:
- Active projects (JobStatusID 1 or 6)
- Fabrication resource assignments
- Completion percentage less than 99%
- Element levels less than 3

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