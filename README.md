# 🏥 Hospital Queue Optimization System

A web application built with **Laravel 12** to reduce hospital waiting time. It replaces a first-come, first-served line with a **priority-based queue**: emergency patients are seen first, and routine patients who have waited a long time are never left behind.

The system has three roles, **Admin**, **Doctor** and **Patient**, and each one gets its own dashboard.

---

## ✨ Features

### 👤 Patient
- Register, log in and manage your profile and settings
- Browse departments and doctors, with their ratings
- **Book appointments** in 15-minute slots (09:00–13:00 and 14:00–17:00)
- **Reschedule** an appointment (up to 2 times) or **cancel** it with a reason, until 2 hours before the visit
- **Walk-in check-in** with an urgency level (Routine / Urgent / Emergency), which gives you a token number
- **Live queue status**: your position and estimated waiting time, updated automatically
- An alert when the doctor calls you
- **Family members**: book and check in for a child or parent from the same account
- **Medical records**: digital prescriptions and visit history, downloadable as PDF
- **Lab tests**: request tests and download the uploaded lab reports
- **Rate the doctor** (1–5 stars, with a comment) after a completed visit
- Email/SMS appointment reminders
- **Bangla / English** language switch

### 🩺 Doctor
- **Priority-ordered queue**: call the next patient, start the consultation and complete the visit
- Write and edit **digital prescriptions** (diagnosis, medicines, dosage, duration)
- View a patient's previous visits and prescriptions
- See today's appointments
- View lab reports for referred patients
- View your own ratings and reviews
- Pause or resume availability with one click

### 🛠️ Admin
- Manage users, departments and doctors (license and background check)
- Manage lab test requests and **upload lab reports** (PDF/JPG/PNG, up to 5 MB)
- Monitor reviews and queue logs
- Generate **PDF reports**

---

## ⚙️ Priority Scheduling Algorithm

The queue uses **non-preemptive priority scheduling with aging**, a CPU scheduling idea from operating systems:

```
priority score = urgency weight + min(minutes waited × 0.5, 60)
```

| Urgency   | Weight |
|-----------|--------|
| Emergency | 1000   |
| Urgent    | 50     |
| Routine   | 10     |

- **Emergency first:** a new emergency patient always ranks above everyone else.
- **Aging prevents starvation:** a routine patient gains 0.5 points per minute of waiting, up to a maximum of 60 points. After about 80 minutes, they rank above a newly arrived urgent patient.
- **Non-preemptive:** a consultation already in progress is never interrupted.

The logic lives in `app/Services/SchedulerService.php`.

---

## 🧰 Tech Stack

| Layer      | Technology                              |
|------------|-----------------------------------------|
| Backend    | Laravel 12, PHP 8.2                     |
| Database   | MySQL                                   |
| Frontend   | Blade, Bootstrap 5.3, Tabler Icons, JavaScript (fetch polling) |
| PDF        | barryvdh/laravel-dompdf                 |
| Testing    | PHPUnit (unit and feature tests)        |
| Local dev  | XAMPP                                   |

---

## 🚀 Installation (local, XAMPP)

**Requirements:** PHP 8.2+, Composer, MySQL (XAMPP), Node.js (optional)

```bash
# 1. Clone the repository
git clone https://github.com/riti202-stack/Hospital-queue-optimization.git
cd Hospital-queue-optimization

# 2. Install dependencies
composer install

# 3. Create the environment file
cp .env.example .env
php artisan key:generate
```

**4. Configure the database.** Create a database named `hospital_queue` in phpMyAdmin, then edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hospital_queue
DB_USERNAME=root
DB_PASSWORD=
```

```bash
# 5. Create the tables and sample data
php artisan migrate --seed

# 6. Start the server
php artisan serve
```

Open **http://127.0.0.1:8000**.

### Optional: reminders

```bash
php artisan schedule:work
```

---

## 🧪 Testing

The project includes **unit, integration, white-box and black-box tests**.

| Type        | What is tested |
|-------------|----------------|
| Unit        | Priority formula, appointment rules, model helpers |
| White-box   | Basis-path testing of `AppointmentRules::cannotChange()` (cyclomatic complexity = 5) |
| Black-box   | Equivalence partitioning and boundary values (dates, ratings 0/1/5/6, file size 5120/5121 KB, phone length) |
| Integration | Booking, check-in → queue, reschedule/cancel, family members, prescriptions, reviews, lab reports, role access |

**Run the tests:**

1. Create an empty database named `hospital_test`. The tests use it, so your real data is never touched.
2. Run:

```bash
php artisan test
```

---

## 📁 Project Structure

```
app/
├── Http/Controllers/     # Admin, Doctor and Patient controllers
├── Http/Middleware/      # Role check, SetLocale
├── Models/               # User, Doctor, Department, Appointment, QueueEntry,
│                         # Dependent, Prescription, DoctorReview, TestRequest
├── Services/             # SchedulerService (priority queue)
├── Support/              # AppointmentRules (slots, cutoff, reschedule limit)
└── Notifications/        # Reminders, prescription and call alerts
resources/views/
├── admin/  doctor/  patient/  layouts/  partials/
lang/bn.json              # Bangla translations
tests/
├── Unit/                 # Algorithm and rule tests
└── Feature/              # Integration tests
```

---

## 📸 Screenshots

| Patient Dashboard | Doctor Queue |
|---|---|
| _add screenshot_ | _add screenshot_ |

| Booking | Admin Panel |
|---|---|
| _add screenshot_ | _add screenshot_ |

---

## 👩‍💻 Author

**Abida Alam Riti**
Department of Computer Science and Engineering
Khulna University of Engineering & Technology (KUET)
GitHub: [@riti202-stack](https://github.com/riti202-stack)

---

## 📄 License

This project was developed for academic purposes.
