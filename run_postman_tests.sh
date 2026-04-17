#!/bin/bash

BASE_URL="http://localhost:8000/api"
RESULTS=()

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}API Test Suite - Final Tasks${NC}"
echo -e "${BLUE}================================${NC}\n"

# Helper function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local token=$4
    local data=$5
    local expected_status=$6

    echo -e "${YELLOW}Testing: ${name}${NC}"

    if [ -z "$token" ]; then
        if [ "$method" = "GET" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" -H "Content-Type: application/json" -d "$data")
        fi
    else
        if [ "$method" = "GET" ]; then
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" -H "Authorization: Bearer $token")
        else
            response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL$endpoint" -H "Authorization: Bearer $token" -H "Content-Type: application/json" -d "$data")
        fi
    fi

    http_code=$(echo "$response" | tail -1)
    body=$(echo "$response" | head -n -1)

    if [[ "$http_code" == "$expected_status"* ]]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        echo "$body"
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_status, got $http_code)"
        echo "$body"
    fi
    echo ""
}

# ===== AUTHENTICATION TESTS =====
echo -e "${BLUE}\n=== AUTHENTICATION ====${NC}\n"

# Login as regular user
echo -e "${YELLOW}Login as regular user${NC}"
USER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"password"}')
USER_TOKEN=$(echo "$USER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}User Token: ${USER_TOKEN:0:20}...${NC}\n"

# Login as admin
echo -e "${YELLOW}Login as admin${NC}"
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}')
ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}Admin Token: ${ADMIN_TOKEN:0:20}...${NC}\n"

# Login as other user
echo -e "${YELLOW}Login as other user${NC}"
OTHER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"other@test.com","password":"password"}')
OTHER_TOKEN=$(echo "$OTHER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}Other User Token: ${OTHER_TOKEN:0:20}...${NC}\n"

# Login as premium user
echo -e "${YELLOW}Login as premium user${NC}"
PREMIUM_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"premium@test.com","password":"password"}')
PREMIUM_TOKEN=$(echo "$PREMIUM_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}Premium User Token: ${PREMIUM_TOKEN:0:20}...${NC}\n"

# ===== TASK 1: CREATE TEST NOTES AND TASKS =====
echo -e "${BLUE}\n=== TASK 1: AUTHORIZATION - CREATE RESOURCES ====${NC}\n"

# Create a note
echo -e "${YELLOW}Create Note${NC}"
NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Note","content":"This is a test note","status":"draft"}')
NOTE_ID=$(echo "$NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Note ID: $NOTE_ID"
echo "$NOTE" | head -c 200
echo -e "\n"

# Create a task
echo -e "${YELLOW}Create Task${NC}"
TASK=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/tasks" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","description":"Task description","status":"pending"}')
TASK_ID=$(echo "$TASK" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Task ID: $TASK_ID"
echo "$TASK" | head -c 200
echo -e "\n"

# ===== TASK 1: NOTE AUTHORIZATION TESTS =====
echo -e "${BLUE}\n=== TASK 1: NOTE AUTHORIZATION ====${NC}\n"

test_endpoint "View own note" "GET" "/notes/$NOTE_ID" "$USER_TOKEN" "" "200"
test_endpoint "View note as other user (should fail)" "GET" "/notes/$NOTE_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "View note as admin (should pass)" "GET" "/notes/$NOTE_ID" "$ADMIN_TOKEN" "" "200"

test_endpoint "Update own note" "PUT" "/notes/$NOTE_ID" "$USER_TOKEN" '{"title":"Updated Note","content":"Updated content"}' "200"
test_endpoint "Update note as other user (should fail)" "PUT" "/notes/$NOTE_ID" "$OTHER_TOKEN" '{"title":"Hacked"}' "403"
test_endpoint "Update note as admin (should pass)" "PUT" "/notes/$NOTE_ID" "$ADMIN_TOKEN" '{"title":"Admin Updated"}' "200"

# ===== TASK 1: TASK AUTHORIZATION TESTS =====
echo -e "${BLUE}\n=== TASK 1: TASK AUTHORIZATION ====${NC}\n"

test_endpoint "View own task" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" "" "200"
test_endpoint "View task as other user (should fail)" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "View task as admin (should pass)" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$ADMIN_TOKEN" "" "200"

test_endpoint "Update own task" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" '{"title":"Updated Task","status":"in_progress"}' "200"
test_endpoint "Update task as other user (should fail)" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" '{"title":"Hacked"}' "403"

# ===== TASK 2: COMMENT TESTS =====
echo -e "${BLUE}\n=== TASK 2: COMMENTS ====${NC}\n"

# Add comment to note
echo -e "${YELLOW}Add comment to note${NC}"
COMMENT=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/comments" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"content":"This is a test comment"}')
COMMENT_ID=$(echo "$COMMENT" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo "Comment ID: $COMMENT_ID"
echo "$COMMENT" | head -c 200
echo -e "\n"

test_endpoint "View comments on note" "GET" "/notes/$NOTE_ID/comments" "$USER_TOKEN" "" "200"
test_endpoint "Add comment to task" "POST" "/notes/$NOTE_ID/tasks/$TASK_ID/comments" "$USER_TOKEN" '{"content":"Task comment"}' "201"

test_endpoint "Edit own comment" "PUT" "/comments/$COMMENT_ID" "$USER_TOKEN" '{"content":"Updated comment"}' "200"
test_endpoint "Edit comment as other user (should fail)" "PUT" "/comments/$COMMENT_ID" "$OTHER_TOKEN" '{"content":"Hacked"}' "403"
test_endpoint "Admin can delete any comment" "DELETE" "/comments/$COMMENT_ID" "$ADMIN_TOKEN" "" "200"

# ===== TASK 3: ATTACHMENT TESTS =====
echo -e "${BLUE}\n=== TASK 3: FILE ATTACHMENTS ====${NC}\n"

echo -e "${YELLOW}Test Premium-only attachment upload${NC}"
test_endpoint "Premium user upload attachment" "POST" "/notes/$NOTE_ID/attachments" "$PREMIUM_TOKEN" '{"file":"test"}' "201"
test_endpoint "Non-premium user upload (should fail)" "POST" "/notes/$NOTE_ID/attachments" "$USER_TOKEN" '{"file":"test"}' "403"

# ===== SUMMARY =====
echo -e "${BLUE}\n================================${NC}"
echo -e "${BLUE}Test Suite Complete${NC}"
echo -e "${BLUE}================================${NC}\n"
