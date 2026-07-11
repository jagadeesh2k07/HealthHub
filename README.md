# 🏥 HealthHub

A full-stack Doctor Appointment Booking System where patients can register, browse doctors, book appointments, and cancel within policy — with a full admin panel to manage everything.

## ✨ Features

- Secure login/register with hashed passwords & PHP sessions
- Role-based access — Patient & Admin
- Book, view, and cancel appointments
- **Cancellation policy:** patients can cancel only if 4+ hours remain before the appointment; admins/doctors can cancel anytime
- Admin panel to manage doctors, appointments, and users (CRUD)
- Forgot-password flow, live email-availability check, SQL-injection-safe queries

## 🛠️ Tech Stack

HTML, CSS, JavaScript · PHP (mysqli) · MySQL

## 📂 Structure

```
healthhub/
├── index.html / login.html / register.html
├── db.php              # DB connection config
├── css/, js/
└── php/                # login, register, dashboard, admin, booking logic
```

## 🚀 Setup

1. Clone the repo and place it in your server's web root (e.g. `htdocs/` for XAMPP)
2. Create a MySQL database `healthhub_db` and run the schema (users, doctors, appointments tables)
3. Update credentials in `db.php` if needed
4. Start Apache + MySQL, then open `http://localhost/healthhub/index.html`

## 📬 Contact

K Jagadeesh — [@jagadeesh2k07](https://github.com/jagadeesh2k07) · support@healthhub.com
