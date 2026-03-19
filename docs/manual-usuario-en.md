# User Manual - SenApre System
## SENA Attendance Management System

**Version**: 1.0  
**Last Updated**: March 2026  
**Language**: English

---

## 📋 Table of Contents

1. [Introduction](#introduction)
2. [System Access](#system-access)
3. [User Roles](#user-roles)
4. [Administrator Panel](#administrator-panel)
5. [Instructor Panel](#instructor-panel)
6. [Leadership Panel](#leadership-panel)
7. [Student Representatives Panel](#student-representatives-panel)
8. [Biometric Facial Recognition](#biometric-facial-recognition)
9. [Troubleshooting](#troubleshooting)

---

## 🎯 Introduction

SenApre is a comprehensive attendance management system for SENA (National Learning Service of Colombia), designed to automate and modernize student attendance control through facial biometric recognition.

### ✨ Main Features

- ✅ **Facial recognition** for attendance registration
- ✅ **Multiple panels** based on user role
- ✅ **Student group (fichas) management**
- ✅ **Real-time reports and statistics**
- ✅ **Access from any device** with camera

---

## 🔑 System Access

### Technical Requirements

- Modern web browser (Chrome, Firefox, Edge, Safari)
- Internet connection
- Webcam (for biometric features)
- Camera permissions enabled

### Access URL

```
https://your-render-url.onrender.com
```

### Initial Credentials

Credentials are assigned by the system administrator. By default:
- **Username**: ID number
- **Password**: ID number (change on first login)

---

## 👥 User Roles

### 1. 🎓 Student (Representative/Vocero)
**Functions**:
- Check attendance history
- Register biometric attendance
- Update personal information
- Receive meeting notifications

**Access**: `index.html` → Student Panel

### 2. 👨‍🏫 Instructor
**Functions**:
- Manage attendance for their student groups
- View student reports
- Generate attendance reports
- Use facial recognition to take attendance

**Access**: `instructor-dashboard.html`

### 3. 🏛️ Wellness Administrative (Leadership)
**Functions**:
- Manage leadership meetings
- Register biometric attendance of leaders
- Manage student representatives
- View participation statistics

**Access**: `liderazgo.html`

### 4. 👔 Director
**Functions**:
- General system dashboard
- User and permission management
- Global attendance reports
- System configuration

**Access**: `admin-dashboard.html`

### 5. ⚙️ System Administrator
**Functions**:
- Complete user management
- Database configuration
- Mass data import
- System maintenance

---

## 🖥️ Administrator Panel

### Main Dashboard

Shows key metrics:
- **Total active groups (fichas)**
- **Registered students**
- **Today's attendance**
- **Pending reports**

### User Management

1. Navigate to **"Users"** in the side menu
2. View user list with role filters
3. Available actions:
   - Create new user
   - Edit information
   - Change password
   - Activate/Deactivate account

### Data Import

**To import students from Excel**:

1. Go to **"Import Data"**
2. Upload Excel files to the `fichas/` folder
3. Run the import script
4. Review the results report

⚠️ **Note**: Files must follow the SENA established format.

---

## 📚 Instructor Panel

### Instructor Dashboard

Shows:
- Assigned student groups (fichas)
- Total students per group
- Today's attendance
- Alerts for students with low attendance

### Taking Attendance

#### Option A: Biometric Attendance (Recommended)

1. Go to **"Attendance"** → **"Take Attendance"**
2. Select student group (ficha)
3. Allow camera access
4. The system will automatically detect faces
5. Recognized students will be marked as present
6. For unrecognized students, use manual option

#### Option B: Manual Attendance

1. Select student group
2. Mark each student as:
   - ✅ Present
   - ❌ Absent
   - 📝 Excuse (upload document)

### Viewing Reports

1. Go to **"Reports"**
2. Select report type:
   - **Daily**: Specific day attendance
   - **Weekly**: Weekly summary
   - **Monthly**: Monthly analysis
   - **By Student**: Individual history
3. Filter by group and dates
4. Export to PDF or Excel

---

## 🏛️ Leadership Panel

### Meeting Management

1. Go to **"Meetings"**
2. Create new meeting:
   - Title
   - Date and time
   - Type (regular/extraordinary)
   - Description
3. System generates QR code for invitation

### Biometric Registration of Leaders

**Before using the system, leaders must register**:

1. Go to **"Biometrics"** → **"Facial Registration"**
2. Select leader from the list
3. Allow camera access
4. Capture 3 face photos (front, left side, right side)
5. Save biometric registration

### Meeting Attendance

1. Go to **"Attendance"** → **"Meetings"**
2. Select active meeting
3. Enable camera for facial recognition
4. Leaders register automatically when their face is detected
5. View real-time statistics:
   - Total invited
   - Present
   - Absent

---

## 🎓 Student Representatives Panel

### My Profile

View and update:
- Personal data
- Assigned student group (ficha)
- Profile photo
- Contact information

### My Attendance

Check:
- Attendance history
- Present/Absent days
- Registered excuses
- Personal statistics

### Biometric Registration

**First use**:

1. Access **"Facial Registration"**
2. Allow camera
3. Follow instructions for face capture
4. Complete the 3 required captures
5. Confirm successful registration

**Mark attendance**:

1. Go to **"Register Attendance"**
2. Allow camera
3. System will automatically recognize your face
4. Confirm attendance registered

---

## 🔐 Biometric Facial Recognition

### How It Works

1. **Capture**: Camera detects faces in real-time
2. **Extraction**: System extracts unique facial features (embeddings)
3. **Comparison**: Searches for matches in the database
4. **Verification**: If similarity ≥ 85%, identifies the user
5. **Registration**: Marks attendance with timestamp

### Recommendations for Better Recognition

✅ **Good lighting** (preferably natural)  
✅ **Uncovered face** (no dark glasses, caps, masks)  
✅ **Look at camera** (front, not profile)  
✅ **Neutral background** (avoid busy backgrounds)  
✅ **Distance 50-100cm** from camera  

❌ **Avoid**:
- Very low or very bright lighting
- Multiple people in the frame
- Sudden movements during capture

---

## ⚠️ Troubleshooting

### Page Not Loading

1. Check Internet connection
2. Clear browser cache (Ctrl + Shift + R)
3. Try in incognito/private mode
4. Verify the URL is correct

### Camera Not Working

1. **Browser permissions**:
   - Click 🔒 security icon (address bar)
   - Allow camera
   - Reload page

2. **Operating system permissions**:
   - Windows: Settings → Privacy → Camera → Allow
   - Mac: System Preferences → Security → Camera

3. **Test camera**:
   - Test at: https://webcamtests.com/
   - Ensure no other app is using the camera

### "No Face Detected"

1. Check adequate lighting
2. Move closer to camera (50cm)
3. Look directly at camera
4. Remove accessories covering face
5. Try in a location with more neutral background

### "User Not Found"

1. Verify you are registered in the system
2. Contact administrator to verify your data
3. Ensure your biometric registration is complete

### Error Importing Excel

1. **Check format**: Must be `.xls` file (not .xlsx)
2. **Required columns**: Document, Names, Last Names, Status
3. **Encoding**: Save in UTF-8 if special characters present
4. **Size**: Files under 10MB

### Cannot Log In

1. **Verify credentials**:
   - Username = ID number (no dots or spaces)
   - Password = Assigned by administrator

2. **Reset password**:
   - Contact system administrator
   - Request password reset

3. **Account locked**:
   - Wait 30 minutes after failed attempts
   - Contact administrator if persists

---

## 📞 Technical Support

### Contact

**SenApre System Administrator**  
📧 Email: soporte@senapre.com  
📱 Phone: [Contact number]  
🕐 Hours: Monday to Friday, 8:00 AM - 5:00 PM

### Reporting Issues

To report a technical problem, include:
1. Detailed description of the issue
2. Screenshot (if applicable)
3. Browser and version used
4. Time and date of incident
5. Affected user

---

## 🔒 Security and Privacy

### Biometric Data Protection

- Facial data is stored encrypted
- No photos saved, only mathematical features (embeddings)
- Restricted access only to authorized users
- Compliance with SENA privacy policies

### Best Practices

- Do not share access credentials
- Log out when finished (especially on public computers)
- Report suspicious activity immediately
- Keep contact information updated

---

## 📱 Device Compatibility

### Recommended Browsers

| Browser | Minimum Version | Biometric Support |
|-----------|---------------|-------------------|
| Chrome | 90+ | ✅ Full |
| Firefox | 88+ | ✅ Full |
| Edge | 90+ | ✅ Full |
| Safari | 14+ | ✅ Full |

### Mobile Devices

- ✅ **Android**: Updated Chrome, functional front camera
- ✅ **iOS**: Safari, iPhone 6s or higher
- ⚠️ **Tablets**: Functional, but recommended for viewing only

---

## 🎓 Glossary

| Term | Definition |
|---------|-----------|
| **Embedding** | Unique mathematical representation of a face |
| **Ficha** | SENA training group |
| **Lectiva** | Theoretical/practical training stage |
| **Productiva** | Company internship stage |
| **Vocero** | Student representative of the group |
| **Biometrics** | Identification using physical characteristics |
| **Confidence** | Similarity percentage in facial recognition |

---

## 🚀 Updates and News

### Version 1.0 (March 2026)

✅ Integrated facial biometric system  
✅ Instructor panel with attendance taking  
✅ Leadership panel for meetings  
✅ Mass import from Excel  
✅ Real-time reports and statistics  

### Upcoming Features

🔜 Push notifications  
🔜 Native mobile app  
🔜 Integration with SENA systems  
🔜 Advanced custom reports  

---

## ✍️ Credits

**Developed by**: SenApre Team  
**Institution**: National Learning Service (SENA)  
**Year**: 2026

---

**© 2026 SenApre - SENA Attendance System. All rights reserved.**
