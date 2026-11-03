# 🏗️ Architecture & Technical Design

This document details the software architecture, data flow, and design patterns utilized in the **Sonali Mutton Shop** application.

---

## 1. System Architecture Overview

The application follows a modular Model-View-Controller (MVC)-inspired structure engineered natively with modern PHP, MySQL, and responsive frontend components.

```mermaid
flowchart TD
    Client["Client Browser / Mobile Device"] -->|"HTTPS / HTTP Requests"| WebServer["Apache Web Server (WAMP / Production)"]
    WebServer -->|".htaccess URL Rewriting"| Router["PHP Request Lifecycle"]
    
    subgraph App["Application Core (PHP 8.x)"]
        Router --> Config["includes/config.php<br/>(URL Detection, Security Headers)"]
        Config --> Lang["includes/lang.php<br/>(Bilingual Marathi/English Engine)"]
        Config --> Session["Secure Session Manager<br/>(SameSite=Lax, HttpOnly)"]
        Config --> DBConn["MySQLi / PDO Connection (utf8mb4)"]
        
        Router --> Storefront["Public Storefront<br/>(Catalog, Search, Product Details)"]
        Router --> UserPortal["User Account Portal<br/>(Orders, Address, Auth)"]
        Router --> AdminSuite["Admin Management Suite<br/>(Products, Orders, Customers, Settings)"]
        
        Storefront --> Cart["Cart & Checkout Engine"]
        Cart --> OrderTx["Transactional Order Processing"]
    end

    subgraph Data["Data Persistence"]
        DBConn --> MySQL[("MySQL Database<br/>sonali_mutton_db")]
        MySQL --> TblProd["products & categories"]
        MySQL --> TblOrder["orders & order_items"]
        MySQL --> TblUser["users & reviews"]
        MySQL --> TblShop["shop_info"]
    end
```

---

## 2. Bilingual Localization Engine

One of the standout features of the application is native support for **Marathi (`mr`)** and **English (`en`)**:
1. **Interface Strings:** Stored as dictionary key-value maps in `includes/lang.php`.
2. **Database Fields:** Catalog items maintain dual columns (`name` and `name_mr`, `description` and `description_mr`).
3. **Fallback Helper:** The custom `dbt($row, 'field')` function checks the user's active session language. If Marathi is chosen and a Marathi value exists, it is served; otherwise, it seamlessly falls back to English.
4. **UTF-8 Support:** Database connections explicitly invoke `utf8mb4` charset configuration to preserve Marathi Unicode typography (Devanagari script).

---

## 3. Database Schema Design

The relational database schema is structured for performance, referential integrity, and customer tracking:

```mermaid
erDiagram
    CATEGORIES ||--o{ PRODUCTS : "contains"
    USERS ||--o{ ORDERS : "places"
    ORDERS ||--|{ ORDER_ITEMS : "comprises"
    PRODUCTS ||--o{ ORDER_ITEMS : "referenced in"
    USERS ||--o{ REVIEWS : "writes"
    USERS ||--o{ CART : "owns"

    CATEGORIES {
        int id PK
        string name
        string name_mr
        string slug
        int sort_order
        boolean is_active
    }

    PRODUCTS {
        int id PK
        int category_id FK
        string name
        string name_mr
        decimal price
        string unit
        string image
        text description
        text description_mr
        boolean is_available
        boolean is_active
    }

    ORDERS {
        int id PK
        string order_number UK
        int user_id FK
        decimal total_amount
        string payment_status
        string order_status
        string customer_name
        string customer_phone
        text shipping_address
        datetime created_at
    }

    ORDER_ITEMS {
        int id PK
        int order_id FK
        int product_id FK
        string product_name
        decimal quantity
        decimal unit_price
        decimal line_total
    }
```

---

## 4. Security Highlights

- **SQL Injection Prevention:** Parameterized prepared statements (`$stmt->prepare()` and `$stmt->bind_param()`) for all dynamic queries.
- **Cross-Site Scripting (XSS) Mitigation:** Output escaping on all dynamic HTML strings with `htmlspecialchars($var, ENT_QUOTES, 'UTF-8')`.
- **Password Hashing:** Passwords encrypted using PHP's standard `password_hash()` algorithm (`BCRYPT` / `ARGON2ID`).
- **Session Protection:** Cookie hardening using `SameSite=Lax` and `HttpOnly` flags to mitigate CSRF and session hijacking.
- **Transactional Integrity:** ACID guarantees for order processing using `$conn->begin_transaction()`, committing only after all line items and cart operations succeed.
