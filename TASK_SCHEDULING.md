# Task Scheduling Algorithm

## Overview
The task scheduling algorithm optimizes task distribution across available work days based on priority, due dates, and available hours per day.

## Algorithm Features

### Inputs
- **Tasks**: List of tasks with priority (1-5), estimated_time (hours), and due_date
- **Available Hours**: Default 6 hours per day (configurable)
- **Start Date**: Starting date for scheduling (default: today)

### Scheduling Logic

#### 1. Priority & Due Date Scoring
Tasks are sorted by urgency score calculated as:

```php
$priorityScore = (6 - $task->priority) * 2; // Higher priority = higher score
$dueDateScore = match(true) {
    $daysUntilDue < 0 => 20,    // Overdue tasks get highest priority
    $daysUntilDue <= 1 => 15,   // Due today/tomorrow  
    $daysUntilDue <= 3 => 10,   // Due within 3 days
    $daysUntilDue <= 7 => 5,    // Due within a week
    default => 1                // Due later
};

$urgencyScore = $priorityScore + $dueDateScore;
```

#### 2. Daily Allocation
- Tasks are allocated to days based on available hours
- Higher urgency score tasks are scheduled first
- Tasks that don't fit in a day are moved to the next day
- Maximum 365 days scheduling horizon to prevent infinite loops

#### 3. Output Structure
```json
{
  "schedule": [
    {
      "date": "2024-01-15",
      "tasks": [
        {
          "id": 1,
          "name": "Task Name",
          "priority": 1,
          "estimated_time": 2.5,
          "due_date": "2024-01-16",
          "is_overdue": false,
          "days_until_due": 1,
          "urgency_score": 25.0
        }
      ],
      "total_hours": 2.5,
      "available_hours": 6.0,
      "is_full_day": false,
      "utilization_rate": 41.67
    }
  ],
  "statistics": {
    "total_days": 5,
    "total_tasks": 10,
    "scheduled_tasks": 10,
    "unscheduled_tasks": 0,
    "total_hours": 25.5,
    "overdue_tasks": 2,
    "overdue_percentage": 20.0,
    "average_utilization": 85.0,
    "start_date": "2024-01-15",
    "end_date": "2024-01-19"
  }
}
```

## API Endpoints

### 1. Personal Schedule (All Roles)
```
GET /api/my-schedule?start_date=2024-01-15&hours_per_day=8
```
- Returns schedule for the authenticated user's assigned tasks
- Available to all users (admin, manager, member)

### 2. Organization Schedule (Admin/Manager Only)
```
GET /api/tasks/schedule?start_date=2024-01-15&hours_per_day=6
```
- **Admin**: Returns schedule for all tasks in the organization
- **Manager**: Returns schedule for tasks in projects assigned to them
- **Member**: Not allowed (403 error)

## Role-Based Access

### Admin
- Can schedule all tasks across the organization
- Has access to organization-wide scheduling

### Manager
- Can schedule tasks from projects assigned to them
- Limited to their project scope

### Member
- Can only schedule their own assigned tasks
- Personal scheduling only

## Usage Examples

### Basic Personal Schedule
```bash
curl -X GET "/api/my-schedule" \
  -H "Authorization: Bearer {token}"
```

### Custom Hours Per Day
```bash
curl -X GET "/api/my-schedule?hours_per_day=8" \
  -H "Authorization: Bearer {token}"
```

### Organization Schedule (Admin/Manager)
```bash
curl -X GET "/api/tasks/schedule?start_date=2024-01-15&hours_per_day=6" \
  -H "Authorization: Bearer {token}"
```

## Algorithm Benefits

1. **Priority Optimization**: Higher priority tasks scheduled first
2. **Due Date Awareness**: Overdue and soon-due tasks get precedence
3. **Resource Utilization**: Maximizes daily hour utilization
4. **Flexibility**: Configurable hours per day
5. **Role Security**: Respects user roles and permissions
6. **Comprehensive Stats**: Provides detailed scheduling statistics

## Clean Code Principles Applied

- **Single Responsibility**: Each method has one clear purpose
- **Service Layer**: Business logic separated from controllers
- **Role-Based Security**: Authorization handled at route/controller level
- **No Code Duplication**: Reusable methods for different user types
- **Clear Naming**: Method and variable names are self-explanatory
- **Type Safety**: Strict typing with PHP 8+ features
