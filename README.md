# AquaTrack: Final Project for Web Systems and Technologies 1

AquaTrack is a water refilling station system for Marikina City. Customers order
refills, new containers, and dispenser rentals for pickup or delivery; staff
manage the orders and inventory; drivers do the deliveries. 

## Requirements

- PHP 8.2 or newer, with the `pdo_mysql` extension
- Composer 2
- MySQL 8

Node and npm are not needed.

## Setup

1. Unzip the project and open a terminal inside the project folder.

2. Install the PHP dependencies:

   ```bash
   composer install
   ```

3. Create your environment file:

   ```bash
   copy .env.example .env     # Windows
   cp .env.example .env       # macOS / Linux
   ```

4. Create the database. Log into MySQL as `root` and run:

   ```sql
   CREATE DATABASE aquatrack CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'aquatrack'@'localhost' IDENTIFIED BY 'aquatrack_dev_pw';
   GRANT ALL PRIVILEGES ON aquatrack.* TO 'aquatrack'@'localhost';
   FLUSH PRIVILEGES;
   ```

   Any local MySQL account works instead. If you use a different one, put its
   name and password into the `DB_USERNAME` and `DB_PASSWORD` lines of `.env`
   so they match.

5. Generate the application key:

   ```bash
   php artisan key:generate
   ```

6. Create the tables and load the sample data:

   ```bash
   php artisan migrate --seed
   ```

7. Start the development server:

   ```bash
   php artisan serve
   ```

## Open it in a browser

Go to **http://127.0.0.1:8000**

## Seed logins

The password for every account below is `password`.

| Role    | Emails                                                                     |
| ------- | -------------------------------------------------------------------------- |
| Admin   | `tom@admin.com`, `jerry@admin.com`                                          |
| Staff   | `althea@staff.com`, `magzy@staff.com`, `clark@staff.com`, `miguel@staff.com`, `lee@staff.com` |
| Driver  | `walter@delivery.com`, `jesse@delivery.com`                                |

There is no customer seed account. Customers sign themselves up at `/register`.

The role is decided by the email domain, not by anything on the login form:

- `@admin.com` → admin
- `@staff.com` → staff
- `@delivery.com` → driver
- anything else → customer

Public registration only ever creates a customer. Internal accounts are added
from the admin user management page.
