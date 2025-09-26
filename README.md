# Data Pemilu 2024

This project is a web application for managing and displaying data from the 2024 Indonesian general election.

## Features

*   View election data by province, regency, district, and village.
*   View detailed information about polling places (TPS).
*   View the final voters list (DPT).
*   Export vote data to Excel.

## Installation

1.  Clone the repository:
    ```bash
    git clone https://github.com/aswandi/data-pemilu-2024.git
    ```
2.  Install dependencies:
    ```bash
    composer install
    npm install
    ```
3.  Create a copy of the `.env.example` file and name it `.env`.
4.  Generate an application key:
    ```bash
    php artisan key:generate
    ```
5.  Configure your database in the `.env` file.
6.  Run the database migrations:
    ```bash
    php artisan migrate
    ```
7.  Start the development server:
    ```bash
    php artisan serve
    ```

## Database

The database schema is defined in the `database/migrations` directory. The main tables are:

*   `provinces`: Provinces in Indonesia.
*   `regencies`: Regencies in Indonesia.
*   `districts`: Districts in Indonesia.
*   `villages`: Villages in Indonesia.
*   `tps`: Polling places.
*   `dpt`: Final voters list.
*   `vote_data`: Vote data.

## API

The API endpoints are defined in the `routes/api.php` file. The available endpoints are:

*   `/api/provinces`: Get a list of provinces.
*   `/api/regencies/{province_id}`: Get a list of regencies in a province.
*   `/api/districts/{regency_id}`: Get a list of districts in a regency.
*   `/api/villages/{district_id}`: Get a list of villages in a district.
*   `/api/tps/{village_id}`: Get a list of polling places in a village.