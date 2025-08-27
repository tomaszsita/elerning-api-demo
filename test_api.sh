#!/bin/bash

# E-Learning API Test Script
# Usage: ./test_api.sh [base_url]
# Example: ./test_api.sh http://localhost

BASE_URL=${1:-http://localhost}
echo "🧪 Testing E-Learning API at: $BASE_URL"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_result() {
    local status=$1
    local message=$2
    if [ "$status" = "success" ]; then
        echo -e "${GREEN}✅ $message${NC}"
    elif [ "$status" = "error" ]; then
        echo -e "${RED}❌ $message${NC}"
    elif [ "$status" = "info" ]; then
        echo -e "${BLUE}ℹ️  $message${NC}"
    elif [ "$status" = "warning" ]; then
        echo -e "${YELLOW}⚠️  $message${NC}"
    fi
}

# Test 1: Health Check
echo -e "\n${BLUE}1. Health Check${NC}"
response=$(curl -s -w "%{http_code}" "$BASE_URL/health")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "200" ]; then
    print_result "success" "Health check passed"
    echo "Response: $body"
else
    print_result "error" "Health check failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 2: List Courses
echo -e "\n${BLUE}2. List Courses${NC}"
response=$(curl -s -w "%{http_code}" "$BASE_URL/courses")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "200" ]; then
    print_result "success" "List courses successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
else
    print_result "error" "List courses failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 3: Enroll User in Course
echo -e "\n${BLUE}3. Enroll User in Course${NC}"
response=$(curl -s -w "%{http_code}" -X POST "$BASE_URL/courses/1/enroll" \
    -H "Content-Type: application/json" \
    -d '{"user_id": 1}')
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "201" ]; then
    print_result "success" "Enrollment successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
elif [ "$http_code" = "400" ]; then
    print_result "warning" "User already enrolled (expected for second run)"
    echo "Response: $body"
else
    print_result "error" "Enrollment failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 4: Get User Courses
echo -e "\n${BLUE}4. Get User Courses${NC}"
response=$(curl -s -w "%{http_code}" "$BASE_URL/users/1/courses")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "200" ]; then
    print_result "success" "Get user courses successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
else
    print_result "error" "Get user courses failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 5: Create Progress
echo -e "\n${BLUE}5. Create Progress${NC}"
response=$(curl -s -w "%{http_code}" -X POST "$BASE_URL/progress" \
    -H "Content-Type: application/json" \
    -d '{"user_id": 1, "lesson_id": 1, "request_id": "test-123"}')
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "201" ]; then
    print_result "success" "Progress creation successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
elif [ "$http_code" = "400" ]; then
    print_result "warning" "Progress already exists (expected for second run)"
    echo "Response: $body"
else
    print_result "error" "Progress creation failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 6: Get User Progress
echo -e "\n${BLUE}6. Get User Progress${NC}"
response=$(curl -s -w "%{http_code}" "$BASE_URL/progress/1/courses/1")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "200" ]; then
    print_result "success" "Get user progress successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
else
    print_result "error" "Get user progress failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 7: Get Progress History
echo -e "\n${BLUE}7. Get Progress History${NC}"
response=$(curl -s -w "%{http_code}" "$BASE_URL/progress/1/lessons/1/history")
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "200" ]; then
    print_result "success" "Get progress history successful"
    echo "Response: $body" | jq '.' 2>/dev/null || echo "Response: $body"
else
    print_result "error" "Get progress history failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test 8: Error Cases
echo -e "\n${BLUE}8. Error Cases${NC}"

# Test invalid user
echo -e "\n${YELLOW}8a. Invalid User Enrollment${NC}"
response=$(curl -s -w "%{http_code}" -X POST "$BASE_URL/courses/1/enroll" \
    -H "Content-Type: application/json" \
    -d '{"user_id": 999}')
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "404" ]; then
    print_result "success" "Invalid user correctly rejected"
    echo "Response: $body"
else
    print_result "error" "Invalid user test failed (HTTP $http_code)"
    echo "Response: $body"
fi

# Test invalid course
echo -e "\n${YELLOW}8b. Invalid Course Enrollment${NC}"
response=$(curl -s -w "%{http_code}" -X POST "$BASE_URL/courses/999/enroll" \
    -H "Content-Type: application/json" \
    -d '{"user_id": 1}')
http_code="${response: -3}"
body="${response%???}"

if [ "$http_code" = "404" ]; then
    print_result "success" "Invalid course correctly rejected"
    echo "Response: $body"
else
    print_result "error" "Invalid course test failed (HTTP $http_code)"
    echo "Response: $body"
fi

echo -e "\n${GREEN}🎉 API Testing completed!${NC}"
echo -e "${BLUE}💡 Tip: Use 'jq' for better JSON formatting${NC}"
echo -e "${BLUE}📚 See POSTMAN_README.md for detailed instructions${NC}"
