# TerSun Calendar
# A calendar for humans 

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
```





# **Custom 13-Month Calendar (Laravel Package)**

A standalone Laravel package for integrating a **custom 13-month, 28-day calendar**. This package overlays the **Gregorian calendar**, incorporates **solunar and tide data**, and includes **moon phases**. It also uses **NOAA data** to enrich the calendar with real-world astronomical information.

---

## **🌟 Features**  

- **✅ 13-month, 28-day calendar system** – Each month has **28 days**, and the year consists of **13 months**.  
- **✅ 1 day of rest** – Each year has **one extra day of rest**, usually placed between months.  
- **✅ First day of the year is the Vernal Equinox** – The calendar starts with the **March Equinox**.  
- **✅ Solunar data** – Includes **best fishing and hunting times**.  
- **✅ Tide data** – Shows **high and low tide levels & times** (fetched from **NOAA API**).  
- **✅ Moon Phases** – Displays **New Moon, Full Moon, First Quarter, etc.**  
- **✅ NOAA integration** – Fetches **water temperature, wind speed, and sunrise/sunset times**.  
- **✅ Normal Calendar Overlay** – Allows **overlaying with the Gregorian calendar**.  

---

## **📀 Installation**  

### **1. Install via Composer**
```sh
composer require 976-tuna/custom-calendar


---

## **📊 Data Import & NOAA Fetch Commands**

### **2. Import Solar Events Data**
```sh
php artisan migrate
php artisan calendar-data:import
```
This will **import solar events** (equinoxes and solstices) into the database.

### **3. Fetch & Store NOAA Stations**
```sh
php artisan noaa:stations
php artisan fetch:noaa-current-stations
php artisan match:noaa-currents
```
### **4. Fetch & Store NOAA Data (Tides, Wind, Moon Phases)**
```sh
php artisan noaa:fetch {days=7}
```
This fetches **NOAA tide and weather data** for the **next 7 days** (or a custom number of days).


```
This retrieves **all NOAA stations** and their **available data products**.

---

## **📊 Viewing Stored Data & Statistics**

### **5. View Last Imported Solar Events**
```sh
php artisan tinker
>>> \App\Models\SolarEvent::latest()->first();
```

### **6. View Last Fetched NOAA Tide Data**
```sh
php artisan tinker
>>> \App\Models\NOAATideForecast::latest()->first();
```

### **7. View NOAA Stations**
```sh
php artisan tinker
>>> \App\Models\NOAAStation::all();
```

---

## **📅 Usage in Blade Template**
You can load the calendar in a Blade view like this:
```blade
@php
    $calendarData = \Tuna976\CustomCalendar\CustomCalendar::generateCalendar();
@endphp

@include('custom-calendar::calendar', ['calendarData' => $calendarData])
```

---

## **🚀 Next Steps**
- **Improve mobile responsiveness** for small screens.
- **Optimize NOAA data fetching** to reduce API requests.
- **Add dynamic user selection** for NOAA stations.

### **💬 Need Help?**
For issues, feel free to create an **[Issue on GitHub](#)** or contact **976-Tuna**.

---

