# E-Learning API - Postman Collection

## 📋 Description

Postman collection for testing the e-learning platform API. Contains all available endpoints and test scenarios.

## 🚀 Installation and Configuration

### 1. Import Collection
1. Open Postman
2. Click "Import" 
3. Select file `postman_collection.json`
4. Collection will be imported

### 2. Configure Variables
1. In the collection click on "Variables" (gear icon)
2. Set the `base_url` variable:
   - **For Docker**: `http://localhost`
   - **For local environment**: `http://localhost:8000`
   - **For other ports**: `http://localhost:PORT`

## 📚 Available Endpoints

### Health Check
- **GET** `/health` - Checks if API is working

### Courses
- **GET** `/courses` - List of all courses
- **POST** `/courses/{id}/enroll` - Enroll in a course

### Users  
- **GET** `/users/{id}/courses` - User's courses

### Progress
- **POST** `/progress` - Create progress
- **GET** `/progress/{user_id}/courses/{course_id}` - User progress in a course
- **DELETE** `/progress/{user_id}/lessons/{lesson_id}` - Delete (reset) progress
- **GET** `/progress/{user_id}/lessons/{lesson_id}/history` - Progress change history

## 🧪 Test Scenarios

### Basic Tests
1. **Health Check** - Check if API is working
2. **List Courses** - Get list of courses
3. **Enroll in Course** - Enroll user in a course

### Error Tests
1. **Already Enrolled Test** - Attempt to re-enroll
2. **Full Course Enrollment Test** - Attempt to enroll in full course
3. **Invalid User Enrollment** - Attempt to enroll non-existent user
4. **Invalid Course Enrollment** - Attempt to enroll in non-existent course

## 📊 Example Responses

### GET /courses
```json
{
  "courses": [
    {
      "id": 1,
      "title": "PHP Fundamentals",
      "description": "Podstawy PHP",
      "max_seats": 20,
      "created_at": "2025-08-26T10:00:00+00:00"
    }
  ]
}
```

### POST /courses/1/enroll
```json
{
  "id": 1,
  "user_id": 1,
  "course_id": 1,
  "status": "enrolled",
  "enrolled_at": "2025-08-26T10:00:00+00:00"
}
```

### GET /progress/1/courses/1
```json
{
  "completed": 2,
  "total": 3,
  "percent": 67,
  "lessons": [
    {
      "id": 1,
      "status": "complete"
    },
    {
      "id": 2,
      "status": "complete"
    },
    {
      "id": 3,
      "status": "pending"
    }
  ]
}
```

## 🔧 Test Data Preparation

Before testing, load test data:

```bash
# In Docker container
docker-compose exec app php bin/console app:load-test-data

# Or locally
php bin/console app:load-test-data
```

Test data contains:
- 4 users (ID: 1-4)
- 3 courses with lessons
- Different numbers of available seats

## 🚨 Error Codes

- **200** - OK
- **201** - Created (course enrollment)
- **204** - No Content (progress deletion)
- **400** - Bad Request (validation errors)
- **404** - Not Found (non-existent user/course)
- **409** - Conflict (course full, already enrolled)

## 💡 Tips

1. **Test order**: First check health check, then list courses
2. **User IDs**: Use IDs 1-4 (test data)
3. **Course IDs**: Use IDs 1-3 (test data)
4. **Request ID**: You can use any string for `request_id` in progress
5. **Reset data**: If you need clean data, run `app:load-test-data` again

## 🔄 Automation

You can run the entire collection automatically:
1. In Postman click "Run collection"
2. Select collection "E-Learning API"
3. Set options (iterations, delay)
4. Click "Run"

## 📝 Notes

- API uses PostgreSQL as database
- All dates are in ISO 8601 format
- IDs are auto-increment
- Progress is reset (not deleted) on DELETE
- Progress history is automatically saved
