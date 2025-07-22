# Laravel Windmill Queue

A custom queue driver for Laravel 5.7+ using **Windmill Protocol** as the transport layer (via HTTP callbacks) and **MySQL as persistent storage**.

---

## 🚀 Features

- Custom queue connector: `windmill`
- Stores job data in **MySQL**
- Uses **HTTP** listener (Windmill Protocol) to trigger job consumption
- Designed for distributed or serverless job workers
- Lightweight and extensible

---

## ⚙️ Requirements

- Laravel 5.7+
- PHP >= 7.1
- MySQL for queue storage
- HTTP accessible consumer endpoint (Windmill)

---

## 📦 Installation

```bash
composer require windmill/laravel-windmill-queue