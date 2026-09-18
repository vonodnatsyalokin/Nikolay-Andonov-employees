# Employees

Finds the pair of employees who have worked together on common projects for the longest period of time.

Input is a CSV file with the format `EmpID, ProjectID, DateFrom, DateTo`, where `DateTo` may be `NULL` (meaning today).

## Structure

- `src/Domain` — the business rules: date ranges, employment records, the pair calculation. Knows nothing about files.
- `src/Input` — reading the outside world: CSV files and the many date formats they contain.

## Notes

The CSV reading is written by hand. In a real project this would be a battle tested package such as `league/csv`, but here the parsing is part of what the task is about, so it did not feel right to hand it off.

## About AI usage

The solution is designed by me and I own every single line of it - AI model was used for the code typing part - only after the design part was ready.

The task is intentionally not AI harnessed - no skills, no rules, no agent instructions, no guardrails, etc.

## Requirements

- PHP 8.2+
- Composer

## Install

```bash
composer install
```

## Test

```bash
composer test
```

Work in progress — usage, output and assumptions are documented as the project grows.
