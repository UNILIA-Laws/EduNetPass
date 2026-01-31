# UNILIA Eduroam Credential Delivery System

[![Laravel](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com)  
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue.svg)](https://www.php.net)  
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**Automated system for sending Eduroam credentials to UNILIA students after they are added to the LDAP directory.**  
Built with Laravel/PHP, this system streamlines the process of generating and distributing network credentials, ensuring timely access for students while reducing manual workload for ICT staff.

---

## Table of Contents

- [Features](#features)  
- [Workflow](#workflow)  
- [Installation](#installation)  


---

## Features

- **Email Notifications** – Sends credentials securely to students via email immediately after creation.  
- **Logging & Auditing** – Tracks all actions including credential generation, email delivery status, and errors.  
- **Error Handling** – Graceful exception handling ensures that failed deliveries are logged and retried.  
- **Secure Storage** – Temporary credentials are handled securely in compliance with best practices.  

---

## Workflow

```mermaid
flowchart LR
    A[New student added to LDAP] --> B[Generate Eduroam credentials]
    B --> C[Send email to student]
    B --> D[Log actions and errors]
    C --> E[Student receives credentials]
    D --> F[ICT staff monitors logs]

