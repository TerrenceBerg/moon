# 976-Tuna Custom Calendar Package

A standalone Laravel package for integrating a custom 13-month, 28-day calendar. This package overlays the normal Gregorian calendar, incorporates solunar and tide data, and includes moon phases. It uses NOAA data to enrich the calendar with real-world astronomical information.

## Features

- **13-month, 28-day calendar system**: Each month has 28 days, and the year consists of 13 months.
- **1 day of rest**: Each year has one day of rest, usually placed between months.
- **First day of the year is the Vernal Equinox**.
- **Solunar data**: The calendar includes solunar information, which is crucial for determining optimal fishing and hunting times.
- **Tide Data**: Tidal information is included to show high and low tides for each day.
- **Moon Phases**: The calendar shows moon phase information, including new moon, full moon, etc.
- **Normal Calendar Overlay**: Allows you to overlay the custom calendar on the regular Gregorian calendar for comparison.

## Installation

### 1. Via Composer

Run the following command to install the package via Composer:

```bash
composer require 976-tuna/custom-calendar
