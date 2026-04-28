# Forecast Management System - SAIC Motor

> **Version:** 2.0 Professional Edition  
> **Stack:** PHP 7.4, Oracle DB (OCI8), Bootstrap 5.3, jQuery 3.6, Select2, SweetAlert2

ระบบสำหรับอัปโหลดไฟล์ Forecast จาก SAIC, แปลงข้อมูลจาก Excel, จัดเก็บลง Oracle, ดูรายการ Forecast, วิเคราะห์ Forecast รายชิ้น และจัดการสิทธิ์ผู้ใช้งาน

---

## โครงสร้างโปรเจค

```text
forecast_saic/
├── index.php                    # หน้า Upload Centre พร้อม Plant/Customer selector
├── list.php                     # หน้า Forecast Records พร้อม filter, pagination, clear data
├── forecast_view.php            # หน้า Forecast Analytics ราย Plant/Part
├── manage_auth.php              # หน้าจัดการสิทธิ์ผู้ใช้ (Admin เท่านั้น)
├── login.php                    # หน้า Login
├── check_type.php               # Diagnostic: ตรวจ type ของ FORECAST_DATE
├── get_customers_ajax.php       # AJAX endpoint โหลดลูกค้าจาก SAP ตาม Plant
├── debug_sap.php                # Diagnostic: ทดสอบ SAP customer query
│
├── api/
│   ├── process_upload.php       # อัปโหลด/parse Excel แล้ว insert/update WEB.FC_FORECAST
│   ├── get_forecasts.php        # ดึง Forecast records พร้อม pagination
│   ├── get_part_list.php        # ดึง Part list สำหรับ Select2 autocomplete
│   ├── get_part_forecast_detail.php  # ดึง Day/Week Forecast ของ Part
│   ├── get_customers.php        # JSON endpoint ดึงรายชื่อลูกค้าจาก SAP
│   ├── clear_data.php           # ลบข้อมูล Forecast ของ Plant ที่เลือก
│   ├── manage_auth.php          # CRUD สิทธิ์ผู้ใช้
│   ├── auth_login.php           # ตรวจสอบ Login + Authorization + Session
│   └── auth_logout.php          # Logout และ redirect ไป login.php
│
├── components/
│   └── sidebar.php              # Sidebar navigation แบบ shared component
│
├── config/
│   ├── conn.php                 # Forecast DB connection -> WEB.FC_FORECAST
│   ├── database.php             # HRMS connection -> HRMS_AUTH
│   ├── intra_db.php             # Intranet user connection -> intra.users
│   └── erp_db.php               # SAP ERP connection -> ECCERP
│
├── css/
│   └── sidebar.css              # Global CSS variables + sidebar styles
│
├── js/                          # Reserved for future JS modules
├── sql/                         # SQL scripts สำหรับ DDL / DML
├── tmp/                         # Diagnostic scripts ชั่วคราว
├── uploads/                     # Temp storage สำหรับไฟล์ Excel ที่อัปโหลด
└── vendor/
    ├── SimpleXLSX.php           # Library parse .xlsx (Shuchkin\SimpleXLSX)
    └── SimpleXLS.php            # Library parse .xls (Shuchkin\SimpleXLS)
```

---

## Database Connections

| Connection | Config | DB/SID | Schema/Table | ใช้สำหรับ |
|---|---|---|---|---|
| Forecast DB | `config/conn.php` | `INTRA01` | `WEB.FC_FORECAST` | ข้อมูล Forecast หลัก |
| HRMS | `config/database.php` | `HRMS` | `HRMS_AUTH` | สิทธิ์ผู้ใช้งาน |
| Intranet | `config/intra_db.php` | `SAGDB` | `intra.users` | Login และข้อมูลพนักงาน |
| SAP ERP | `config/erp_db.php`, `get_customers_ajax.php`, `api/get_customers.php` | `ECCERP` | `sapsr3.VBAK`, `sapsr3.KNA1` | รายชื่อลูกค้าตาม Plant |

หมายเหตุ: README นี้ไม่บันทึก password ของ database ให้ดูเฉพาะในไฟล์ config บนเครื่องที่ได้รับสิทธิ์เท่านั้น

---

## ตารางหลัก: `WEB.FC_FORECAST`

| Column | Type | คำอธิบาย |
|---|---|---|
| `FORECAST_ID` | RAW(16) | Primary Key สร้างด้วย `SYS_GUID()` |
| `CUSTOMER_CODE` | VARCHAR2 | รหัสลูกค้าจาก SAP (`KUNNR`), ถ้าไม่ได้เลือกจะบันทึกเป็น `UNKNOWN` |
| `PLANT` | VARCHAR2 | รหัส Plant เช่น `1101`, `1800` |
| `PART_NO` | VARCHAR2 | รหัสชิ้นส่วน ถ้าเป็นตัวเลขล้วนจะถูก padding เป็น 18 หลักตอนบันทึก |
| `PART_NAME` | VARCHAR2 | ชื่อชิ้นส่วน |
| `FORECAST_TYPE` | VARCHAR2(1) | `D` = Day, `W` = Week |
| `FORECAST_DATE` | VARCHAR2(8) | วันที่รูปแบบ `YYYYMMDD` |
| `FORECAST_QTY` | NUMBER | จำนวน Forecast |
| `FORECAST_STATUS` | VARCHAR2(1) | `A` = Active, `X` = Inactive/Superseded |
| `CUSTOMER_ISSUE_DATE` | VARCHAR2(12) | วันที่ออกเอกสารจาก SAIC รูปแบบ `YYYYMMDDHHMM` |
| `CREATED_BY` | VARCHAR2 | รหัสผู้บันทึกจาก Session, default เป็น `SYSTEM` |
| `CREATED_AT` | DATE | วันเวลาที่ insert |

### ตารางสิทธิ์: `HRMS_AUTH`

| Column | คำอธิบาย |
|---|---|
| `AUT_ID` | Primary Key |
| `CODEMPID` | รหัสพนักงาน |
| `AUT_NAME` | ชื่อพนักงาน |
| `AUT_PRIVILEGE` | สิทธิ์ระบบ เช่น `FORECAST` หรือ `SYSTEM` |
| `AUT_LEVEL` | `9` = User, `99` = Admin |
| `AUT_TYPE` | `User` หรือ `Admin` |
| `AUT_ACTIVE` | `Y` = เปิดใช้งาน, `N` = ปิดใช้งาน |
| `CREATE_DATE` | วันที่สร้างสิทธิ์ |

### SAP Customer Source

Customer dropdown ใช้ข้อมูลจาก SAP ERP:

```sql
SELECT t1.kunnr, t2.name1
FROM sapsr3.VBAK t1
JOIN sapsr3.KNA1 t2 ON t1.mandt = t2.mandt AND t1.kunnr = t2.kunnr
WHERE t1.mandt = '400'
  AND t1.vkorg = :plant
GROUP BY t1.kunnr, t2.name1
ORDER BY t2.name1 ASC
```

---

## Plant Codes

| Code | Plant |
|---|---|
| `1101` | SAAB |
| `1100` | SAB |
| `1800` | SAM |
| `1400` | SATC |
| `9001` | SDC |
| `1200` | SLAB |
| `1202` | SLAB2 |
| `1203` | SLAB3 |
| `1201` | SRAB |
| `1300` | SRDC |

Login จะ map `USERS_COSTCENTER` เป็น `$_SESSION['plant_no']` ด้วย prefix ต่อไปนี้:

| Cost center prefix | Plant |
|---|---|
| `10` | `1100` |
| `11` | `1101` |
| `20` | `1200` |
| `21` | `1201` |
| `22` | `1202` |
| `23` | `1203` |
| `30` | `1300` |
| `40` | `1400` |

---

## หน้าใช้งาน

### `login.php`

- รับ username/password จากพนักงาน
- `api/auth_login.php` ตรวจ user จาก `intra.users`
- ตรวจสิทธิ์ซ้ำจาก `HRMS_AUTH` โดยต้องมี `AUT_ACTIVE = 'Y'` และ `AUT_PRIVILEGE` เป็น `FORECAST` หรือ `SYSTEM`
- เมื่อสำเร็จจะสร้าง Session สำหรับชื่อผู้ใช้, รหัสพนักงาน, ระดับสิทธิ์ และ Plant

### `index.php` - Upload Centre

- ต้อง Login ก่อนเข้าใช้งาน
- เลือก Plant ก่อน ระบบจะโหลด Customer จาก `get_customers_ajax.php`
- รองรับไฟล์ `.xlsx` และ `.xls`
- Drag & Drop หรือคลิกเลือกไฟล์
- ส่ง `plant`, `customer`, `forecast_file` ไปที่ `api/process_upload.php`
- แสดงผล New / Updated / Duplicates ด้วย SweetAlert2
- เมื่อสำเร็จ redirect ไป `forecast_view.php`

### `list.php` - Forecast Records

- กรองข้อมูลตาม Plant และ keyword (`PART_NO`, `PART_NAME`)
- Pagination: 10 / 20 / 50 / 100 rows (default หน้า UI = 20)
- แสดงวันที่จาก `FORECAST_DATE` เป็น `DD/MM/YYYY`
- แสดง badge `DAY` / `WEEK`
- ปุ่ม **Clear Plant Data** เรียก `api/clear_data.php` เพื่อลบข้อมูลของ Plant นั้นแบบ physical delete

### `forecast_view.php` - Forecast Analytics

- เลือก Plant และค้นหา Part ด้วย Select2 autocomplete
- Select2 เรียก `api/get_part_list.php` พร้อม `plant` และ `q`
- แสดง Day Forecast และ Week Forecast แยกกัน
- ถ้าไม่เลือก Part จะโหลด top 50 parts ของ Plant จาก `api/get_part_forecast_detail.php`

### `manage_auth.php` - Permission Management

- เข้าได้เฉพาะ Admin (`aut_level >= 99`)
- เพิ่ม / แก้ไข / ลบ สิทธิ์ผู้ใช้ผ่าน `api/manage_auth.php`
- Lookup ชื่อพนักงานจาก `intra.users`
- เก็บสิทธิ์ลง `HRMS_AUTH`

---

## API และ Endpoints

### `POST api/process_upload.php`

อัปโหลดและประมวลผลไฟล์ Excel

**Request:** `multipart/form-data`

| Field | Required | คำอธิบาย |
|---|---:|---|
| `plant` | Yes | รหัส Plant |
| `customer` | No | รหัสลูกค้าจาก SAP, ถ้าว่างจะใช้ `UNKNOWN` |
| `forecast_file` | Yes | ไฟล์ `.xlsx` หรือ `.xls` |

**Response:**

```json
{
  "status": "success",
  "details": {
    "total": 150,
    "new": 100,
    "updated": 30,
    "duplicates": 20
  },
  "message": "Process Complete: 100 New, 30 Updated, 20 Skipped."
}
```

**Logic หลัก:**

1. อ่าน ISSUE Date จาก Row 4 เป็น `YYYYMMDDHHMM`
2. Scan ตั้งแต่ Row 14 เพื่อหา `Part NO.`, `Part Name`, `Day Forecast`, `Week Forecast`
3. จับคู่ `Date` row กับ `Qty` row ถัดไป
4. แปลง Part Number ที่เป็นตัวเลขล้วนเป็น 18 หลัก
5. แปลงวันที่เป็น `YYYYMMDD`
6. ถ้าพบ record ซ้ำแบบ exact: skip
7. ถ้าพบ Plant + Part + Date + Type เดิมแต่ Qty ต่าง: update record เดิมเป็น `X` แล้ว insert ใหม่เป็น `A`
8. ถ้าไม่พบ record เดิม: insert ใหม่เป็น `A`

### `GET get_customers_ajax.php`

Endpoint ที่หน้า `index.php` ใช้โหลด Customer dropdown

**Query Params:** `plant` (required)

**Response แบบ text:**

```text
0001234567|||Customer Name A
0009876543|||Customer Name B
```

กรณี error จะตอบ `ERROR:...` และกรณีไม่พบข้อมูลจะตอบ `EMPTY:...`

### `GET api/get_customers.php`

JSON endpoint สำหรับดึงรายชื่อลูกค้าจาก SAP ตาม Plant

**Query Params:** `plant` (required)

**Response:**

```json
[
  { "kunnr": "0001234567", "name1": "Customer Name A" }
]
```

### `GET api/get_forecasts.php`

ดึงรายการ Forecast พร้อม Pagination

**Query Params:** `plant`, `search`, `page`, `limit`

**Response:**

```json
{
  "status": "success",
  "data": [],
  "pagination": {
    "total": 500,
    "page": 1,
    "limit": 20,
    "total_pages": 25
  }
}
```

หมายเหตุ: `PART_NAME` ถูกแปลงจาก `TIS-620` เป็น `UTF-8`, และ `PART_NO` ที่เป็นตัวเลขจะถูกตัด zero ด้านหน้าเพื่อแสดงผล

### `GET api/get_part_list.php`

ดึง Part list สำหรับ Select2 autocomplete

**Query Params:** `plant` (required), `q` (keyword)

**Response:** สูงสุด 20 rows

```json
[
  { "PART_NO": "12345", "PART_NAME": "Part Name" }
]
```

### `GET api/get_part_forecast_detail.php`

ดึง Day/Week Forecast ของ Part หรือ top 50 parts ของ Plant

**Query Params:** `plant` (required), `part_no` (optional)

- มี `part_no`: single part mode
- ไม่มี `part_no`: top 50 parts mode

**Response:**

```json
{
  "status": "success",
  "data": [
    {
      "part_info": { "PART_NO": "12345", "PART_NAME": "Part Name" },
      "daily": [{ "F_DATE": "2025-05-26", "F_QTY": "100" }],
      "weekly": [{ "F_DATE": "2025-06-02", "F_QTY": "500" }]
    }
  ]
}
```

### `POST api/clear_data.php`

ลบข้อมูล Forecast ทั้งหมดของ Plant แบบ physical delete

**Request:** `plant` (POST body)

**Response:**

```json
{
  "status": "success",
  "message": "Successfully cleared 500 records for Plant 1800."
}
```

### `GET/POST api/manage_auth.php`

จัดการ Authorization ผู้ใช้ ต้องเป็น Admin เท่านั้น

| Action | Method | คำอธิบาย |
|---|---|---|
| `list` | GET | ดึงรายการ user ที่มี privilege `FORECAST` หรือ `SYSTEM` |
| `lookup` | GET | ค้นหาชื่อพนักงานจาก `intra.users` ด้วย `codempid` |
| `save` | POST | Insert หรือ Update สิทธิ์ |
| `delete` | POST | ลบสิทธิ์ตาม `auth_id` |

### `POST api/auth_login.php`

ตรวจสอบ username/password, สิทธิ์ Forecast, แล้วสร้าง Session

### `GET api/auth_logout.php`

ล้าง Session และ redirect ไป `login.php`

---

## Session & Authorization

| Session Key | คำอธิบาย |
|---|---|
| `$_SESSION['user_id']` | ID ผู้ใช้จาก `intra.users` |
| `$_SESSION['user_code']` | รหัสพนักงาน ใช้ตรวจว่า Login แล้ว |
| `$_SESSION['user_name']` | ชื่อผู้ใช้ |
| `$_SESSION['fullname']` | ชื่อเต็ม ใช้แสดงใน Sidebar |
| `$_SESSION['codcomp']` | Company code |
| `$_SESSION['plant_no']` | Plant ที่ map จาก cost center |
| `$_SESSION['aut_level']` | ระดับสิทธิ์: `9` = User, `99` = Admin |
| `$_SESSION['aut_type']` | ประเภทสิทธิ์ เช่น `User`, `Admin` |
| `$_SESSION['aut_name']` | ชื่อจาก `HRMS_AUTH` |

- ทุกหน้าหลักตรวจ `$_SESSION['user_code']` ถ้าไม่มีจะ redirect ไป `login.php`
- `manage_auth.php` และ `api/manage_auth.php` ตรวจสิทธิ์ Admin
- Sidebar แสดงเมนู Admin Tools เฉพาะ `aut_level == 99`

---

## Frontend Libraries

| Library | Version | ใช้สำหรับ |
|---|---|---|
| Bootstrap | 5.3.0 | Layout และ components |
| Font Awesome | 6.4.0 | Icons |
| Inter (Google Fonts) | - | Typography |
| jQuery | 3.6.0 | AJAX และ DOM |
| SweetAlert2 | 11 | Modal, confirm, alert |
| Select2 | 4.1.0-rc.0 | Autocomplete dropdown |
| Select2 Bootstrap 5 Theme | 1.3.0 | Select2 styling |

---

## Excel Upload Format

```text
Row 4   : ISSUE Date : 2025-05-26 06:41
Row 14+ : Data rows
  - Part NO. : XXXXX   Part Name : YYYYYYY
  - Day Forecast หรือ Week Forecast
  - Date   | MM/DD/YY | MM/DD/YY | ...
  - Qty    | 100      | 200      | ...
```

### Date Parsing

รองรับหลายรูปแบบ:

- `MM/DD/YY` แปลงเป็น `YYYYMMDD`
- `YYYY-MM-DD` ใช้ `strtotime()`
- รูปแบบอื่นจะตัด non-digit ออกด้วย `preg_replace('/[^0-9]/', '', ...)`

---

## Design System

### CSS Variables (`css/sidebar.css`)

```css
--primary: #e30613;
--primary-dark: #b3050f;
--sidebar-width: 260px;
```

### Color Palette

| ใช้สำหรับ | Color |
|---|---|
| Primary / Brand | `#e30613` (SAIC Red) |
| Hover / Dark | `#b3050f` |
| Background | `#f8fafc` -> `#e2e8f0` |
| Day Badge | `#ecfdf5` / `#059669` |
| Week Badge | `#eff6ff` / `#2563eb` |

---

## Known Notes & Gotchas

> **Date Storage**  
> `FORECAST_DATE` เก็บเป็น `VARCHAR2` รูปแบบ `YYYYMMDD` ไม่ใช่ DATE type จึงต้องใช้ `SUBSTR` ตอน format วันที่ใน SQL/API

> **Encoding**  
> ข้อมูลบางส่วนจาก Intranet/SAG ใช้ `TIS-620` หรือ `WE8DEC` จึงมีการแปลงเป็น `UTF-8` ด้วย `iconv()` ในหลาย endpoint

> **Customer Encoding**  
> `get_customers_ajax.php` เชื่อม SAP ด้วย `AL32UTF8` และตอบกลับเป็น plain text สำหรับ dropdown ในหน้า upload

> **Clear Data**  
> `api/clear_data.php` ใช้ `DELETE FROM WEB.FC_FORECAST WHERE PLANT = :plant` เป็นการลบถาวร ไม่มี undo

> **Diagnostic Files**  
> `debug_sap.php`, `check_type.php` และไฟล์ใน `tmp/` ใช้ตรวจสอบระบบ/connection ไม่ใช่ flow หลักของผู้ใช้ทั่วไป

---

*Last updated: 2026-04-28*
