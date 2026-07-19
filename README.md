# 🏥 HealthHub

A full-stack Doctor Appointment Booking System where patients can register, browse doctors, book appointments, and cancel within policy — with a full admin panel to manage everything.

## 🔗 Live Links

- **Live App (fully functional):** [healthhub.free.je](http://healthhub.free.je) — PHP + MySQL backend, all features work here
- **UI Preview only (static, no backend):** [healthhub](https://healthhub-jagadeesh.vercel.app/) — frontend pages only; login/register/booking will not work here since Vercel doesn't run PHP

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

## 🚀 Local Setup

1. Clone the repo and place it in your server's web root (e.g. `htdocs/` for XAMPP)
2. Create a MySQL database `healthhub_db` and run the schema (users, doctors, appointments tables)
3. Update credentials in `db.php` if needed
4. Start Apache + MySQL, then open `http://localhost/healthhub/index.html`

## ☁️ Deployment

Hosted live on [InfinityFree](https://infinityfree.com) at [healthhub.free.je](http://healthhub.free.je), which runs the PHP backend and MySQL database.

> Note: SSL certificate is currently being provisioned — the site may briefly show as "Not secure" until the free Let's Encrypt certificate finishes issuing.

## 📬 Contact

K Jagadeesh — [@jagadeesh2k07](https://github.com/jagadeesh2k07) · support@healthhub.com
