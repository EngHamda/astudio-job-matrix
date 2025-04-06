# Job Filtering System
# Table of Contents

- [Overview](#overview)
- [Basic Usage](#basic-usage)
- [Query Parameter Format](#query-parameter-format)
- [Supported Filter Types](#supported-filter-types)
- [Logical Operators & Grouping](#logical-operators--grouping)
- [Schema Design](#schema-design)
- [Architecture / Implementation Details](#architecture--implementation-details)
- [Job API Documentation](#job-api-documentation)
  - [API Overview](#api-overview)
  - [Endpoints](#endpoints)
    - [GET /api/jobs](#get-apijobs)
  - [Error Handling](#error-handling)



## Overview

The Job Filtering System provides a robust mechanism for filtering job listings through complex query conditions. The
system supports logical operators (AND/OR), condition grouping, and multiple filter types to enable powerful search
capabilities.

## Basic Usage

Jobs can be filtered using the `filter` query parameter:

> **GET** /api/jobs?filter=CONDITION

- `CONDITION` may be a single expression or a nested logical combination.
- Examples:
    - Simple:
      `/api/jobs?filter=job_type=full-time`
    - Combined:
      `/api/jobs?filter=salary_min>=50000 AND is_remote=true`
    - Nested:
      `/api/jobs?filter=(job_type=full-time AND (languages:name HAS_ANY (PHP,JavaScript))) AND attribute:Experience Level>=3`

## Query Parameter Format

The query parameter format supports complex filtering operations through a structured syntax. This section describes in detail how to construct filter expressions.

### General Format

```
filter=EXPRESSION
```

Where `EXPRESSION` can be a simple condition or a complex logical combination of conditions.

### Basic Conditions

Basic conditions follow this format:

```
field OPERATOR value
```

- `field`: A column name from the core_jobs table (e.g., title, salary_min)
- `OPERATOR`: A comparison operator (=, !=, >, <, >=, <=, LIKE, IN)
- `value`: The value to compare against

Examples:
- `job_type=full-time`
- `salary_min>=50000`
- `title LIKE %developer%`
- `job_type IN (full-time,contract)`

### Relationship Conditions

Relationship conditions filter based on related models:

```
relation:column OPERATOR (value1,value2,...)
```

- `relation`: The name of the relationship (languages, locations, categories)
- `column`: (Optional) The specific column to filter on (e.g., name, city)
- `OPERATOR`: A relationship operator (EXISTS, HAS_ANY, HAS_ALL, IS_ANY)
- `(value1,value2,...)`: A comma-separated list of values enclosed in parentheses

Examples:
- `languages:name HAS_ANY (PHP,JavaScript)`
- `locations:city IS_ANY (New York,Remote)`
- `categories:name=Design`

### EAV (Dynamic Attribute) Conditions

EAV conditions filter based on dynamic attributes:

```
attribute:AttributeName OPERATOR value
```

- `attribute:`: Prefix indicating an EAV filter
- `AttributeName`: The name of the attribute (e.g., Experience Level, Years of Experience)
- `OPERATOR`: A comparison operator matching the attribute type
- `value`: The value to compare against

Examples:
- `attribute:Experience Level=Senior Level`
- `attribute:Years of Experience>=5`
- `attribute:Relocation Possible=true`

### Complex Expressions

Conditions can be combined using logical operators:

```
condition1 LOGICAL_OPERATOR condition2
```

- `condition1`, `condition2`: Simple conditions or grouped expressions
- `LOGICAL_OPERATOR`: AND or OR

Examples:
- `job_type=full-time AND is_remote=true`
- `salary_min>=50000 OR salary_max>=80000`

### Grouping

Parentheses can be used to group conditions and control evaluation order:

```
(condition1 AND condition2) OR condition3
```

Examples:
- `(job_type=full-time AND is_remote=true) OR (job_type=contract AND salary_min>=100000)`
- `(languages:name HAS_ANY (PHP,JavaScript)) AND (attribute:Experience Level IN (Mid Level,Senior Level))`

### Complex Example

```
(job_type=full-time AND (languages:name HAS_ANY (PHP,JavaScript))) AND (locations:city IS_ANY (New York,Remote)) AND attribute:Experience Level=Senior Level
```

This filter will find:
- Full-time jobs
- That require either PHP or JavaScript
- Located in either New York or Remote
- Requiring Senior Level experience

## Supported Filter Types

The system supports three categories of filters, each with its own operators:

1. **Basic Filters** (CoreJob columns)
    - **Text/String** (e.g., `title`, `description`, `company_name`)
      Operators: `=`, `!=`, `LIKE`
    - **Numeric** (e.g., `salary_min`, `salary_max`)
      Operators: `=`, `!=`, `>`, `<`, `>=`, `<=`
    - **Boolean** (e.g., `is_remote`)
      Operators: `=`, `!=`
    - **Enum** (e.g., `job_type`, `status`)
      Operators: `=`, `!=`, `IN`
    - **Date** (e.g., `published_at`, `created_at`)
      Operators: `=`, `!=`, `>`, `<`, `>=`, `<=`

2. **Relationship Filters** (related models)
    - **Syntax:** `relation OPERATOR (value1,value2,…)`
    - **Operators:**
        - `EXISTS`   – relationship exists
        - `HAS_ANY`  – any of the specified values
        - `HAS_ALL`  – all of the specified values
        - `IS_ANY`   – match any one of the specified values
    - **Examples:**
        - `languages:name HAS_ANY (PHP,JavaScript)`
        - `locations:city IS_ANY (New York,Remote)`

3. **EAV Filters** (dynamic attributes)
    - **Syntax:** `attribute:NAME OPERATOR value`
    - **Select Attributes** (e.g., `experience_level`)
      Operators: `=`, `!=`, `IN`
    - **Text Attributes**
      Operators: `=`, `!=`, `LIKE`
    - **Number Attributes**
      Operators: `=`, `!=`, `>`, `<`, `>=`, `<=`
    - **Boolean Attributes**
      Operators: `=`, `!=`

---

_All attribute types (text, number, boolean) share the same comparison operators as their basic counterparts._

## Logical Operators & Grouping

The filtering syntax supports full logical expressions, including nesting:

- **Operators**
    - `AND` – both conditions must be true
    - `OR`  – at least one condition must be true

- **Grouping**
    - Use parentheses `()` to control evaluation order and nest expressions
    - Parentheses must be balanced

- **Behavior**
    - You can combine any number of conditions
    - Nested groups are evaluated before their parent expression

- **Example**
    - `(job_type=full-time AND is_remote=true) OR (job_type=contract AND salary_min>=100000)`

## Schema Design

The database schema for The application:

### Core Tables

1. **core_jobs**
   * Contains essential job listing information (title, description, salary ranges, etc.)
   * Uses appropriate data types for each field
   * Includes status field for job lifecycle management
   * Timestamps for creation and updates

2. **attributes**
   * Stores metadata about dynamic attributes
   * Includes type information for proper validation and display
   * Support for options (e.g., for select fields)
   * Required flag for validation

3. **job_attribute_values**
   * Implements the Entity-Attribute-Value (EAV) pattern
   * Allows for flexible, schema-less attributes on jobs
   * References both job and attribute

### Relationship Tables

1. **languages** & **job_language**
   * Many-to-many relationship between jobs and programming languages
   * Normalized design

2. **locations** & **job_location**
   * Many-to-many relationship between jobs and locations
   * Normalized design with city, state, country fields

3. **categories** & **job_category**
   * Many-to-many relationship between jobs and job categories
   * Normalized design

## Architecture / Implementation Details

The system follows a modular design with clear separation of concerns:

- **FilterParser**: Parses complex filter strings into structured arrays
- **ConditionParser**: Identifies and categorizes individual conditions (Splits each condition into field, operator, and value)
- **Condition Handlers**: Apply the appropriate filtering logic to database queries
    - `BasicConditionHandler`: Applies filters on CoreJob columns
    - `RelationshipConditionHandler`: Applies filters on relationships
    - `EavConditionHandler`: Applies filters on EAV attributes
- **JobFilterService**: Orchestrates the parsing and handler execution to build the final Eloquent query

---
# Job API Documentation

## API Overview

The Job API provides endpoints to search, filter, and retrieve job listings using a powerful and flexible query system.

## Endpoints

### GET /api/jobs

Retrieves a list of jobs with optional filtering.

#### Query Parameters

| Parameter | Type   | Description                                     | Required |
|-----------|--------|-------------------------------------------------|----------|
| filter    | string | Complex filter query string                     | No       |
| page      | int    | Page number for pagination                      | No       |
| per_page  | int    | Items per page for pagination                   | No       |
| sort      | string | Field to sort by (prefix with - for descending) | No       |

#### Filter Format

See the README documentation for detailed filter syntax.

#### Response Format

```json
{
    "data": [
        {
            "id": 1,
            "title": "Senior PHP Developer",
            "description": "Experience with Laravel required",
            "company_name": "TechCorp",
            "salary_min": 80000.00,
            "salary_max": 120000.00,
            "is_remote": true,
            "job_type": "full-time",
            "status": "published",
            "published_at": "2025-03-01T00:00:00Z",
            "created_at": "2025-02-15T00:00:00Z",
            "updated_at": "2025-02-15T00:00:00Z",
            "languages": [
                {
                    "id": 1,
                    "name": "PHP"
                },
                {
                    "id": 3,
                    "name": "JavaScript"
                }
            ],
            "locations": [
                {
                    "id": 5,
                    "city": "Remote"
                }
            ],
            "categories": [
                {
                    "id": 2,
                    "name": "Backend Development"
                }
            ],
            "attributes": {
                "Experience Level": 5,
                "framework": "Laravel",
                "team_size": 8
            }
        }
        /* more jobs... */
    ],
    "links": {
        "first": "https://example.com/api/jobs?page=1",
        "last": "https://example.com/api/jobs?page=5",
        "prev": null,
        "next": "https://example.com/api/jobs?page=2"
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 5,
        "path": "https://example.com/api/jobs",
        "per_page": 15,
        "to": 15,
        "total": 72
    }
}
```

## Error Handling

The API returns standard HTTP status codes:

- 200: Success
- 400: Bad Request (invalid filter syntax)
- 404: Job not found
- 500: Server error

Error responses include a message field explaining the error:

```json
{
  "error": true,
  "message": "Invalid filter syntax: Unbalanced parentheses in filter expression"
}
```
