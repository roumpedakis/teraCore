# ΕΛΛΗΝΙΚΟ MyData API - Ολοκληρωμένη Τεκμηρίωση

## 📋 Περιγραφή

Το **MyData (My Data, My Rules)** είναι η ελληνική υπηρεσία ανταλλαγής δεδομένων που επιτρέπει στους πολίτες να:
- Εξουσιοδοτούν εφαρμογές τρίτων να αποκτούν πρόσβαση στα δεδομένα τους
- Ελέγχουν ποιες πληροφορίες μοιράζονται
- Διαχειρίζονται τα δικαιώματα πρόσβασης

## 🔑 Κύρια Features

✅ OAuth2/OpenID Connect Authentication  
✅ JWT Token-based Authorization  
✅ Structured Data Access  
✅ Audit Logging  
✅ Revocation Management  
✅ Consent Management  

## 📁 Δομή Εγγράφων

- `ENDPOINTS.md` - Όλα τα API endpoints
- `AUTHENTICATION.md` - OAuth2 & JWT implementation
- `DATA_SCHEMAS.md` - Δομή δεδομένων  
- `ERP_INTEGRATION.md` - Ολοκλήρωση με ERP systems
- `PAYROLL_INTEGRATION.md` - Ολοκλήρωση με Payroll systems
- `EXAMPLES.md` - Πρακτικά παραδείγματα
- `ERROR_CODES.md` - Κώδικες σφαλμάτων

## 🚀 Γρήγορη Έναρξη

### 1. Δηλώστε την Εφαρμογή σας
Εγγραφείτε στο MyData developer portal και λάβετε:
- `client_id`
- `client_secret`
- `redirect_uri`

### 2. Authorization Flow
```
1. Ανακατεύθυνση χρήστη στο MyData login
2. Λήψη authorization code
3. Ανταλλαγή για access token
4. Χρήση token για API calls
```

### 3. Κλήση API
```bash
curl -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  https://api.mydata.gov.gr/v1/user/profile
```

## 📊 Υποστηριζόμενα Δεδομένα

### Ταυτοτικά Στοιχεία (Identity)
- ΑΦΜ (TAX ID)
- Όνομα πολίτη
- Ημερομηνία γέννησης
- Διεύθυνση
- Τηλέφωνο/Email

### Φορολογικά Δεδομένα (Tax)
- Φορολογικές δηλώσεις
- Μισθολογικά δεδομένα
- Εισοδήματα
- Αποδόσεις

### Εργασιακά Δεδομένα (Employment)
- Ιστορικό απασχόλησης
- Μισθολογίες
- Ασφαλιστικά δεδομένα
- Άδειες

### Ακίνητα (Real Estate)
- Καταστάσεις ακινήτων
- Φόροι ιδιοκτησίας
- Εκτιμήσεις

## 🔒 Ασφάλεια

✅ OAuth2 Authorization Code Flow  
✅ JWT Tokens με expiration  
✅ HTTPS/TLS Encryption  
✅ Scope-based permissions  
✅ Rate Limiting  
✅ Audit Logging  

## 📞 Υποστήριξη

- **Official**: https://www.mydata.gov.gr/
- **Dev Portal**: https://dev.mydata.gov.gr/
- **Documentation**: https://docs.mydata.gov.gr/

---

**Δημιουργία**: Φεβρουάριος 2026  
**Version**: 1.0  
**Status**: Production Ready
