<!--
Αυτό το αποθετήριο είναι μια προσωπική προσπάθεια του συγγραφέα να δημιουργήσει
ένα modular PHP framework (teraCore) με τη βοήθεια του συνεργάτη του έργου.
Χρησιμοποιείται για ανάπτυξη, δοκιμές και πειραματισμό — κάθε νέα λειτουργία
συνοδεύεται από αντίστοιχο test και τεκμηρίωση όπου είναι δυνατόν.
-->

# teraCore - PHP 8.4 Modular MVC Framework

## Σχέδιο

Ένα σταθερό framework βασισμένο σε PHP 8.4, MySQL, και jQuery με:
- MVC Architecture
- Modular System με auto-scanning
- Factory Patterns
- RESTful API support
- Multiple input/output formats (JSON, XML, FORM)

## Δομή Φακέλων

```
project/
├── public/           # Entry point
├── app/
│   └── core/        # Framework core
│       ├── classes/ # Base classes
│       ├── handlers/# URL, Session, Cookie handlers
│       ├── libraries/ # Encrypt, Parser, Sanitizer
│       └── ...
│   └── modules/     # User modules
├── config/          # Configuration & .env
├── tests/           # Unit & Feature tests
└── storage/logs/    # Application logs
```

## Setup

1. Copy `.env.example` to `.env`
2. Edit `.env` with your database credentials
3. Run database installer
4. Start development server

## Installation

```bash
php -S localhost:8000 -t public/
```

---
Ανάπτυξη: Σταδιακή δημιουργία framework core + modules
