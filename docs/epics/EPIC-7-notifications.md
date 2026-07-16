# EPIC 7 — Notifications System

## 🎯 Goal

Real-time + email notifications engine.

## Events

- TaskAssigned
- TaskCompleted
- CommentMentioned
- TaskOverdue

## Channels

- In-app notifications
- Email notifications

## Tasks

- Notification model
- Event + Listener system
- Queue workers (Redis + Horizon)
- Notification center UI

## Acceptance Criteria

- All major events trigger notifications
- Users can mark as read/unread
