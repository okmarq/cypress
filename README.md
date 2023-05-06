# Laravel Practical Task Requirements

- Create an admin panel using any template you prefer with a super admin login feature.
- In the admin panel the super admin should be able to see the dates of the calendar and create an activity for each day. The admin may add a maximum of 4 activities for each day.
- Each activity should have the following fields
  - Title
  - Description
  - Image
- Each activity may be edited and deleted from the admin panel
- When an activity is added/edited/deleted, it should reflect for each of the users. For example,
  - when the admin creates an activity, the given activity should be added for all the users.
  - The same for editing and deleting.
- The admin should see all the registered users with their activities.
- The admin should be able to edit the activity that is added for the user.
  - For example, if an activity is added for the user globally, the admin should be able to edit that particular activity and the changes should not reflect in the global activity.
- Once an activity is edited for a particular user, the global changes should not affect it.
- The admin can also add an activity for the given user, without adding a global activity.
- Please provide the Postman collection, where we have the following Rest

## APIs

- user registration
- user login
- get logged in user’s activities by a range of dates, for example, the range of dates may look like this “1/3/22 - 5/3/22”
