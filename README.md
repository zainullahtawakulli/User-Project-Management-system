# Full Stack Project Management System

A modern full-stack project management and administration system built with **Laravel** and **Vue 3**. The application provides a secure, role-based environment for managing users, projects, tasks, permissions, and system activity from a centralized dashboard.

## 🚀 Overview

This project is designed as a complete full-stack application with a powerful Laravel REST API backend and a responsive Vue 3 frontend.

It includes authentication, database-driven role and permission management, project and task management, activity tracking, and administrative controls such as force logout and temporary user impersonation.

The application is structured as a single repository containing both the backend and frontend:

```text
full_stack_vue/
├── backend/     # Laravel REST API
├── frontend/    # Vue 3 application
└── README.md
```

## ✨ Key Features

* 🔐 **Secure Authentication**

  * User registration and login
  * Laravel Sanctum API authentication
  * Secure token-based sessions
  * Automatic logout when authentication expires

* 👥 **Role & Permission Management**

  * Database-driven roles
  * Dynamic permissions
  * Permission-based route protection
  * Super Admin and Admin access control
  * Flexible role/permission configuration

* 📊 **Project Management**

  * Create and manage projects
  * Project status tracking
  * Project start and due dates
  * Project member management

* ✅ **Task Management**

  * Create and assign tasks
  * Task status tracking
  * Task priorities
  * Due dates
  * Project-based task assignment

* 📝 **Activity Logs**

  * Track important system actions
  * User activity history
  * Login/logout tracking
  * Project and task changes
  * Role and permission changes
  * Detailed before/after changes

* 🛡️ **Administrative Controls**

  * Force logout users
  * Deactivate user accounts
  * Temporary administrator user impersonation
  * Automatic temporary session expiration
  * Server-side permission enforcement

* 🎨 **Modern UI**

  * Vue 3
  * Pinia
  * PrimeVue
  * Tailwind CSS
  * Lucide icons
  * Responsive dashboard design
  * Toast notifications

## 🏗️ Technology Stack

### Backend

* **Laravel**
* **PHP**
* **MySQL**
* **Laravel Sanctum**
* **Spatie Laravel Activitylog**
* RESTful API architecture

### Frontend

* **Vue 3**
* **Vite**
* **Pinia**
* **PrimeVue**
* **Tailwind CSS**
* **Axios**
* **Vue Router**
* **Lucide Icons**
* **Vue Sonner**

## 🔐 Authorization Architecture

The application uses database-backed RBAC (Role-Based Access Control).

Permissions are stored in the database and assigned to roles through a many-to-many relationship:

```text
User
  ↓
Role
  ↓
Permissions
```

Example permissions:

```text
users.view
users.create
users.update
users.delete

projects.view
projects.create
projects.update
projects.delete

tasks.view
tasks.create
tasks.update
tasks.delete

roles.view
roles.create
roles.update
roles.delete

activity_logs.view
```

The backend always validates permissions, while the frontend uses the same permissions to control navigation and UI actions.

## 🔒 Authentication & Session Security

Authentication is implemented using Laravel Sanctum.

The application supports:

* Login
* Registration
* Logout
* Token revocation
* Account activation/deactivation
* Automatic frontend logout on `401 Unauthorized`
* Server-side active-user validation

Administrative temporary login sessions can also be configured with a limited lifetime.

## 📋 Activity Logging

Important application events are recorded using **Spatie Laravel Activitylog**.

Examples include:

```text
User logged in
User logged out
Administrator forced user logout
Administrator force logged in as user
Project created
Project updated
Task created
Task assigned
Task status changed
Project member added
Project member removed
```

Sensitive information such as passwords and authentication tokens is never stored in activity logs.

## 📁 Project Structure

```text
full_stack_vue/
│
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   ├── Models/
│   │   └── ...
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── routes/
│   ├── config/
│   └── ...
│
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── stores/
│   │   ├── services/
│   │   └── router/
│   ├── public/
│   └── ...
│
└── README.md
```

## ⚙️ Installation

### Clone the repository

```bash
git clone <your-repository-url>
cd full_stack_vue
```

### Backend

```bash
cd backend
composer install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=full_stack
DB_USERNAME=root
DB_PASSWORD=
```

Run migrations and seeders:

```bash
php artisan migrate --seed
```

Start the Laravel development server:

```bash
php artisan serve
```

The API will be available at:

```text
http://127.0.0.1:8000
```

### Frontend

Open another terminal:

```bash
cd frontend
npm install
```

Configure the API URL in `.env`:

```env
VITE_API_URL=http://127.0.0.1:8000/api
```

Start the Vue development server:

```bash
npm run dev
```

## 🧪 Development

Backend:

```bash
cd backend
php artisan serve
```

Frontend:

```bash
cd frontend
npm run dev
```

Check Laravel routes:

```bash
php artisan route:list
```

Clear Laravel caches:

```bash
php artisan optimize:clear
```

Check Git status:

```bash
git status
```

## 🔄 Git Workflow

After making changes:

```bash
git add .
git commit -m "Describe your changes"
git push origin main
```

## 🎯 Project Goals

The main goal of this project is to provide a clean foundation for a scalable administration and project management platform while demonstrating modern full-stack development practices.

The project focuses on:

* Clean API architecture
* Secure authentication
* Database-driven authorization
* Reusable frontend components
* Maintainable code structure
* Administrative controls
* Comprehensive activity tracking
* Responsive user experience

## 📌 Future Improvements

Potential future enhancements include:

* Dashboard analytics
* Email notifications
* Advanced reporting
* File and document management
* Real-time notifications
* Advanced task filtering
* Project dashboards
* Automated testing
* Docker deployment
* Production deployment configuration

## 👨‍💻 Author

Developed as a full-stack application using Laravel and Vue 3.

---

**Laravel • Vue 3 • MySQL • Sanctum • PrimeVue • Pinia • Tailwind CSS**
