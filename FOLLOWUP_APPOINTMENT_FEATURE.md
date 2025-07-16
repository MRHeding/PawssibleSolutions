# Follow-up Appointment Feature

## Overview
This feature allows administrators and veterinarians to schedule follow-up appointments for pets after completing or reviewing an appointment. The system automatically pre-fills the appointment details with the same pet and veterinarian information.

## How to Use

### For Administrators

1. **From Appointments List:**
   - Go to `admin/appointments.php`
   - Find a completed appointment 
   - Click the blue calendar-plus icon (📅+) in the Actions column
   - This will take you directly to the follow-up scheduling section

2. **From Appointment Details:**
   - Go to any appointment detail page (`admin/view_appointment.php`)
   - Scroll down to the "Schedule Follow-up Appointment" section
   - Fill in the required information:
     - **Follow-up Date**: Select a future date
     - **Follow-up Time**: Choose from available time slots
     - **Reason for Follow-up**: Enter the purpose (e.g., "Post-surgery check-up")
     - **Additional Notes**: Optional notes for the appointment
   - Click "Schedule Follow-up"

### For Veterinarians

1. **From Appointments List:**
   - Go to `vet/appointments.php`
   - Find a completed appointment
   - Click the blue calendar-plus icon (📅+) in the Actions column

2. **From Appointment Details:**
   - Go to any appointment detail page (`vet/view_appointment.php`)
   - Use the same process as administrators described above

## Features

### Automatic Pre-filling
- **Pet Information**: Same pet as the original appointment
- **Veterinarian**: Same vet as the original appointment (admin can see vet name, vets schedule for themselves)
- **Appointment Number**: Automatically generated unique number

### Conflict Detection
- The system checks if the selected veterinarian already has an appointment at the chosen time
- Prevents double-booking automatically
- Shows error message if conflict exists

### Time Slot Management
- Pre-defined time slots from 9:00 AM to 5:00 PM
- 30-minute intervals available
- Easy dropdown selection

### Conditional Display
- Follow-up scheduling section only appears for:
  - Completed appointments
  - Scheduled appointments
- Section is hidden for cancelled or no-show appointments

## Quick Access Links

The feature includes quick access buttons:
- **📅+ Icon**: Direct link to follow-up scheduling
- **#followup anchor**: Automatically scrolls to the follow-up section when clicked

## Success Indicators

When a follow-up appointment is successfully scheduled:
- Green success message appears
- Shows the new appointment number
- Appointment appears in the regular appointments list
- Client receives the same notifications as regular appointments

## Error Handling

The system handles several error scenarios:
- **Missing Information**: All required fields must be filled
- **Time Conflicts**: Prevents scheduling at occupied time slots
- **Database Errors**: Shows user-friendly error messages
- **Permission Checks**: Ensures only authorized users can schedule

## Technical Details

### Database Integration
- Uses existing `appointments` table
- Leverages `AppointmentNumberGenerator` class
- Maintains data integrity with foreign key relationships

### Security Features
- Session-based authentication required
- Role-based access control (admin/vet only)
- SQL injection protection with prepared statements
- Input validation and sanitization

## Testing Checklist

1. ✅ Schedule follow-up as admin
2. ✅ Schedule follow-up as vet  
3. ✅ Verify conflict detection works
4. ✅ Check appointment appears in listings
5. ✅ Test quick access buttons
6. ✅ Verify permission restrictions
7. ✅ Test with different appointment statuses

## Files Modified

- `admin/view_appointment.php` - Added follow-up scheduling UI and logic
- `admin/appointments.php` - Added quick access button
- `vet/view_appointment.php` - Added follow-up scheduling UI and logic  
- `vet/appointments.php` - Added quick access button
- `test_followup_feature.php` - Testing script (can be removed in production)

## Future Enhancements

Potential improvements for future versions:
- Email notifications for follow-up appointments
- Bulk follow-up scheduling
- Follow-up reminders
- Custom follow-up templates
- Integration with calendar systems
