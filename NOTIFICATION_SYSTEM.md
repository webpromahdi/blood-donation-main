# 🔔 NOTIFICATION SYSTEM DESIGN

## Blood Donation & Emergency Request Management System

---

## 📋 SYSTEM OVERVIEW

### Design Principles

| Principle            | Description                                                        |
| -------------------- | ------------------------------------------------------------------ |
| **Minimal**          | Only notify when action is required or status changes meaningfully |
| **Actionable**       | Each notification should inform or prompt a specific action        |
| **Role-appropriate** | Users only see notifications relevant to their responsibilities    |
| **No spam**          | Avoid redundant or excessive notifications                         |

### Notification Types (Database `type` field)

| Type           | Purpose                           |
| -------------- | --------------------------------- |
| `info`         | General information, FYI          |
| `success`      | Positive outcome confirmation     |
| `warning`      | Attention needed, potential issue |
| `error`        | Something failed or was rejected  |
| `request`      | Blood request related             |
| `donation`     | Donation lifecycle related        |
| `announcement` | System-wide announcements         |

---

## 👤 ADMIN NOTIFICATIONS

Admin monitors system activity and approves/rejects users and requests.

| #   | Notification                     | Trigger Event                          | Why Important                                                   | Action                   |
| --- | -------------------------------- | -------------------------------------- | --------------------------------------------------------------- | ------------------------ |
| A1  | **New Hospital Registration**    | Hospital submits registration          | Admin must verify and approve hospitals before they can operate | Review & Approve/Reject  |
| A2  | **New Donor Registration**       | Donor registers (if approval required) | Quality control for donor verification                          | Review & Approve/Reject  |
| A3  | **New Blood Request (Hospital)** | Hospital creates blood request         | Admin must approve requests before donors see them              | Review & Approve/Reject  |
| A4  | **New Blood Request (Seeker)**   | Seeker creates blood request           | Admin must verify seeker requests                               | Review & Approve/Reject  |
| A5  | **Emergency Request Alert**      | Request marked as "emergency" urgency  | Requires immediate admin attention                              | Prioritize Review        |
| A6  | **Voluntary Donation Submitted** | Donor submits voluntary donation offer | Admin assigns to appropriate hospital                           | Review & Assign Hospital |
| A7  | **Donation Completed**           | Donation marked as completed           | Tracking system health, generate reports                        | View Details             |
| A8  | **Donation Cancelled**           | Donor cancels accepted donation        | May need to find replacement donor                              | View Request             |

### Admin Notification Summary

- **Total: 8 notifications**
- **Focus: Approvals, emergencies, system oversight**
- **NOT notified for:** Routine status updates, chat messages, minor changes

---

## 🩸 DONOR NOTIFICATIONS

Donor receives notifications about requests, their donations, and account status.

| #   | Notification                       | Trigger Event                                              | Why Important                         | Action                |
| --- | ---------------------------------- | ---------------------------------------------------------- | ------------------------------------- | --------------------- |
| D1  | **Account Approved**               | Admin approves donor registration                          | Donor can now access full features    | Start Donating        |
| D2  | **Account Rejected**               | Admin rejects donor registration                           | Donor knows why and can reapply       | View Reason           |
| D3  | **New Matching Request**           | Blood request approved matching donor's blood group + city | Core feature - donor can help someone | View & Accept         |
| D4  | **Emergency Request (Matching)**   | Emergency request matching donor's profile                 | Urgent - life may be at stake         | View & Accept         |
| D5  | **Donation Accepted Confirmation** | Donor accepts a blood request                              | Confirms their commitment             | View Details          |
| D6  | **Appointment Scheduled**          | Hospital schedules appointment for donation                | Donor needs to know when/where        | View Appointment      |
| D7  | **Appointment Reminder**           | 24 hours before scheduled appointment                      | Reduce no-shows                       | View Appointment      |
| D8  | **Donation Completed**             | Hospital marks donation as complete                        | Confirmation + certificate available  | Download Certificate  |
| D9  | **Voluntary Donation Approved**    | Admin approves voluntary donation offer                    | Donor knows their offer was accepted  | View Details          |
| D10 | **Voluntary Donation Rejected**    | Admin rejects voluntary donation                           | Donor knows outcome + reason          | View Reason           |
| D11 | **Hospital Assigned (Voluntary)**  | Hospital assigned for voluntary donation                   | Donor knows where to go               | View Hospital Details |
| D12 | **Eligibility Restored**           | 56 days passed since last donation                         | Donor can donate again                | Update Availability   |
| D13 | **Achievement Unlocked**           | Milestone reached (5, 10, 25 donations)                    | Recognition and motivation            | View Certificate      |

### Donor Notification Summary

- **Total: 13 notifications**
- **Focus: Requests matching their profile, donation lifecycle, achievements**
- **NOT notified for:** Requests outside their city/blood group, other donors' activities

---

## 🏥 HOSPITAL NOTIFICATIONS

Hospital manages blood requests, appointments, and donation completion.

| #   | Notification                 | Trigger Event                             | Why Important                     | Action                 |
| --- | ---------------------------- | ----------------------------------------- | --------------------------------- | ---------------------- |
| H1  | **Account Approved**         | Admin approves hospital registration      | Hospital can now create requests  | Start Using System     |
| H2  | **Account Rejected**         | Admin rejects hospital registration       | Hospital knows outcome            | View Reason            |
| H3  | **Blood Request Approved**   | Admin approves hospital's request         | Request is now visible to donors  | Monitor Responses      |
| H4  | **Blood Request Rejected**   | Admin rejects hospital's request          | Hospital knows why + can resubmit | View Reason            |
| H5  | **Donor Accepted Request**   | Donor accepts hospital's blood request    | Hospital knows help is coming     | View Donor Details     |
| H6  | **Donor On The Way**         | Donor updates status to "on_the_way"      | Hospital can prepare              | Prepare for Arrival    |
| H7  | **Donor Reached**            | Donor updates status to "reached"         | Hospital knows donor arrived      | Begin Donation Process |
| H8  | **Donor Cancelled**          | Donor cancels after accepting             | Hospital may need backup donor    | Find Alternative       |
| H9  | **Request Fulfilled**        | All units collected for a request         | Request cycle complete            | Close Request          |
| H10 | **Voluntary Donor Assigned** | Admin assigns voluntary donor to hospital | Hospital schedules appointment    | Schedule Appointment   |
| H11 | **Voluntary Donation Ready** | Scheduled time approaching (24h)          | Hospital prepares for walk-in     | Prepare                |

### Hospital Notification Summary

- **Total: 11 notifications**
- **Focus: Request lifecycle, donor arrivals, voluntary donations**
- **NOT notified for:** Other hospitals' activities, system-wide stats

---

## 🔍 SEEKER NOTIFICATIONS

Seeker tracks their blood requests and finds donors.

| #   | Notification           | Trigger Event                              | Why Important                            | Action                 |
| --- | ---------------------- | ------------------------------------------ | ---------------------------------------- | ---------------------- |
| S1  | **Request Submitted**  | Seeker submits blood request               | Confirmation of submission               | Track Request          |
| S2  | **Request Approved**   | Admin approves seeker's request            | Request is now active, donors can see it | Track Progress         |
| S3  | **Request Rejected**   | Admin rejects seeker's request             | Seeker knows outcome + reason            | View Reason / Resubmit |
| S4  | **Donor Found**        | First donor accepts the request            | Hope - help is on the way                | View Progress          |
| S5  | **Donor On The Way**   | Donor starts journey to hospital           | Real-time awareness                      | Track                  |
| S6  | **Donation Completed** | Donor completes donation for their request | One unit fulfilled                       | View Progress          |
| S7  | **Request Fulfilled**  | All requested units collected              | Request complete, patient helped         | View Summary           |
| S8  | **Request Expired**    | Required date passed without fulfillment   | Seeker can create new request            | Create New Request     |

### Seeker Notification Summary

- **Total: 8 notifications**
- **Focus: Request status updates, donor progress**
- **NOT notified for:** Internal admin actions, other seekers' requests

---

## 🔄 NOTIFICATION FLOW DIAGRAMS

### Flow 1: Blood Request Lifecycle

```
SEEKER/HOSPITAL creates request
       ↓
   [S1/--] Request Submitted (to requester)
       ↓
   [A3/A4] New Blood Request (to Admin)
       ↓
  Admin Reviews
    ↙     ↘
Approve    Reject
   ↓         ↓
[S2/H3]   [S3/H4]
   ↓
Donors with matching blood group + city notified
   ↓
[D3/D4] New Matching Request (to eligible Donors)
   ↓
Donor Accepts
   ↓
[D5] Confirmation (to Donor)
[H5] Donor Accepted (to Hospital)
[S4] Donor Found (to Seeker)
   ↓
Donation Progress Updates
[D6→D8, H6→H9, S5→S7]
```

### Flow 2: Voluntary Donation

```
DONOR submits voluntary donation offer
       ↓
   [A6] Voluntary Donation Submitted (to Admin)
       ↓
  Admin Reviews
    ↙     ↘
Approve    Reject
   ↓         ↓
[D9]      [D10]
   ↓
Admin assigns Hospital
   ↓
[D11] Hospital Assigned (to Donor)
[H10] Voluntary Donor Assigned (to Hospital)
   ↓
Hospital schedules appointment
   ↓
[D6] Appointment Scheduled (to Donor)
   ↓
[D7/H11] Reminder 24h before
   ↓
Donation completed
   ↓
[D8] Donation Completed (to Donor)
```

### Flow 3: Account Registration

```
USER registers
       ↓
(If Hospital/Donor requiring approval)
       ↓
   [A1/A2] New Registration (to Admin)
       ↓
  Admin Reviews
    ↙     ↘
Approve    Reject
   ↓         ↓
[D1/H1]   [D2/H2]
```

---

## 📊 NOTIFICATION PRIORITY MATRIX

| Priority        | Condition                         | Example             |
| --------------- | --------------------------------- | ------------------- |
| 🔴 **Critical** | Emergency requests, cancellations | D4, A5, H8, S8      |
| 🟠 **High**     | Approvals/rejections, donor found | A1-A4, D1-D3, H3-H5 |
| 🟡 **Medium**   | Status updates, appointments      | D6-D8, H6-H7, S5-S6 |
| 🟢 **Low**      | Confirmations, achievements       | D5, D13, S1         |

---

## 🚫 WHAT WE ARE NOT NOTIFYING (Intentionally)

| Scenario                                   | Reason                                     |
| ------------------------------------------ | ------------------------------------------ |
| Every chat message                         | Would be spam; chat has its own indicator  |
| When donor views a request                 | No action taken yet                        |
| Daily/weekly summaries                     | Overkill for academic project              |
| Other users' registrations (to non-admins) | Not their concern                          |
| Blood inventory levels                     | Out of scope for this system               |
| Password change confirmations              | Standard security, not notification-worthy |
| Profile updates                            | User initiated, they know                  |

---

## 📈 STATISTICS

| Role         | Total Notifications              | Approval | Status Update | Action Required |
| ------------ | -------------------------------- | -------- | ------------- | --------------- |
| **Admin**    | 8                                | -        | 2             | 6               |
| **Donor**    | 13                               | 4        | 6             | 3               |
| **Hospital** | 11                               | 4        | 4             | 3               |
| **Seeker**   | 8                                | 3        | 4             | 1               |
| **TOTAL**    | **40 unique notification types** |

---

## ✅ FINAL VERDICT

### Is This Notification System GOOD TO IMPLEMENT?

## ✅ YES — APPROVED FOR IMPLEMENTATION

### Strengths:

1. **Minimal & Focused** — 40 notifications covering all critical flows without spam
2. **Role-appropriate** — Each user only sees what's relevant to them
3. **Actionable** — Most notifications lead to a specific action
4. **Complete Coverage** — All major flows covered (registration → request → donation → completion)
5. **Emergency Handling** — Critical notifications are clearly prioritized
6. **Academic + Real-world Ready** — Professional enough for production, demonstrable for viva

### Implementation Notes:

- All notifications use the existing `notifications` table schema
- `related_type` and `related_id` fields link to relevant entities (request, donation, etc.)
- Notifications are created server-side when trigger events occur
- Frontend polls or uses the existing dropdown component to display

### For Viva Demonstration:

You can showcase:

1. Admin approving a request → Donor gets notification
2. Donor accepting → Hospital + Seeker get notifications
3. Donation completion → Certificate notification to Donor
4. Emergency request flow with priority highlighting

---

**This design is ready for implementation.**
