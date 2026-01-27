# 🚀 Quick Test Credentials

> **Universal Password**: `password`

---

## 🏪 Vendors (2 Users)

| Email | Password |
|-------|----------|
| testvendor1@10mg.com | password |
| testvendor2@10mg.com | password |

---

## 💰 Lenders (2 Users)

| Email | Password |
|-------|----------|
| testlender1@10mg.com | password |
| testlender2@10mg.com | password |

---

## 👨‍💼 Admin

| Email | Password |
|-------|----------|
| testadmin@10mg.com | password |

---

## 🔧 Quick Commands

### Seed Test Data
```bash
php artisan db:seed --class=TestDatabaseSeeder
```

### Or individually
```bash
php artisan db:seed --class=TestVendorLenderSeeder
```

---

## 📝 Notes

- ✅ All users are **verified** and **active**
- ✅ All have **roles assigned** (vendor/lender)
- ✅ All have **businesses created**
- ✅ Password: `password`
