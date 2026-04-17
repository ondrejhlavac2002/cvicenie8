#!/bin/bash

BASE_URL="http://localhost:8000/api"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

PASS=0
FAIL=0

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}API Test Suite - Final Tasks (FIXED)${NC}"
echo -e "${BLUE}================================${NC}\n"

# Helper function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local token=$4
    local data=$5
    local expected_status=$6

    echo -ne "${YELLOW}${name}${NC} ... "

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
        PASS=$((PASS+1))
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_status, got $http_code)"
        FAIL=$((FAIL+1))
    fi
}

# ===== AUTHENTICATION TESTS =====
echo -e "${BLUE}=== AUTHENTICATION ====${NC}\n"

echo -ne "${YELLOW}Login as regular user${NC} ... "
USER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"password"}')
USER_TOKEN=$(echo "$USER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ Token obtained${NC}\n"

echo -ne "${YELLOW}Login as admin${NC} ... "
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}')
ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ Token obtained${NC}\n"

echo -ne "${YELLOW}Login as other user${NC} ... "
OTHER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"other@test.com","password":"password"}')
OTHER_TOKEN=$(echo "$OTHER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ Token obtained${NC}\n"

echo -ne "${YELLOW}Login as premium user${NC} ... "
PREMIUM_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"premium@test.com","password":"password"}')
PREMIUM_TOKEN=$(echo "$PREMIUM_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ Token obtained${NC}\n"

# ===== CREATE TEST RESOURCES =====
echo -e "${BLUE}=== CREATE TEST RESOURCES ====${NC}\n"

NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Note","body":"This is a test note","status":"draft"}')
NOTE_ID=$(echo "$NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo -e "${GREEN}✓ Note created (ID: $NOTE_ID)${NC}\n"

TASK=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/tasks" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","description":"Task description","status":"pending"}')
TASK_ID=$(echo "$TASK" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)
echo -e "${GREEN}✓ Task created (ID: $TASK_ID)${NC}\n"

# ===== TASK 1: NOTE AUTHORIZATION =====
echo -e "${BLUE}=== TASK 1: NOTE AUTHORIZATION ====${NC}\n"

test_endpoint "View own note" "GET" "/notes/$NOTE_ID" "$USER_TOKEN" "" "200"
test_endpoint "View note as other user" "GET" "/notes/$NOTE_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "View note as admin" "GET" "/notes/$NOTE_ID" "$ADMIN_TOKEN" "" "200"
test_endpoint "Update own note" "PUT" "/notes/$NOTE_ID" "$USER_TOKEN" '{"title":"Updated Note","body":"Updated"}' "200"
test_endpoint "Update note as other user" "PUT" "/notes/$NOTE_ID" "$OTHER_TOKEN" '{"title":"Hacked"}' "403"
test_endpoint "Update note as admin" "PUT" "/notes/$NOTE_ID" "$ADMIN_TOKEN" '{"title":"Admin Updated"}' "200"
test_endpoint "Delete own note (restore)" "DELETE" "/notes/$NOTE_ID" "$USER_TOKEN" "" "200"

# Recreate note for next tests
NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Note 2","body":"Test","status":"draft"}')
NOTE_ID=$(echo "$NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

TASK=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/tasks" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","description":"Task","status":"pending"}')
TASK_ID=$(echo "$TASK" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

echo ""

# ===== TASK 1: TASK AUTHORIZATION =====
echo -e "${BLUE}=== TASK 1: TASK AUTHORIZATION ====${NC}\n"

test_endpoint "View own task" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" "" "200"
test_endpoint "View task as other user" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "View task as admin" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$ADMIN_TOKEN" "" "200"
test_endpoint "Update own task" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" '{"title":"Updated Task"}' "200"
test_endpoint "Update task as other user" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" '{"title":"Hacked"}' "403"
test_endpoint "Update task as admin" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$ADMIN_TOKEN" '{"title":"Admin Updated"}' "200"

echo ""

# ===== TASK 2: COMMENTS =====
echo -e "${BLUE}=== TASK 2: COMMENTS ====${NC}\n"

test_endpoint "View comments on note" "GET" "/notes/$NOTE_ID/comments" "$USER_TOKEN" "" "200"
test_endpoint "Add comment to note" "POST" "/notes/$NOTE_ID/comments" "$USER_TOKEN" '{"body":"Test comment"}' "201"

COMMENT=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/comments" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Comment to edit"}')
COMMENT_ID=$(echo "$COMMENT" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

test_endpoint "View comments on task" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID/comments" "$USER_TOKEN" "" "200"
test_endpoint "Add comment to task" "POST" "/notes/$NOTE_ID/tasks/$TASK_ID/comments" "$USER_TOKEN" '{"body":"Task comment"}' "201"
test_endpoint "Edit own comment" "PUT" "/comments/$COMMENT_ID" "$USER_TOKEN" '{"body":"Updated comment"}' "200"
test_endpoint "Edit comment as other user" "PUT" "/comments/$COMMENT_ID" "$OTHER_TOKEN" '{"body":"Hacked"}' "403"
test_endpoint "Admin delete comment" "DELETE" "/comments/$COMMENT_ID" "$ADMIN_TOKEN" "" "200"

echo ""

# ===== TASK 3: FILE ATTACHMENTS =====
echo -e "${BLUE}=== TASK 3: FILE ATTACHMENTS ====${NC}\n"

# Create a premium user's note for attachment testing
PREMIUM_NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $PREMIUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Premium Note","body":"For attachments","status":"draft"}')
PREMIUM_NOTE_ID=$(echo "$PREMIUM_NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

test_endpoint "Non-premium user upload (should fail)" "POST" "/notes/$PREMIUM_NOTE_ID/attachments" "$USER_TOKEN" '{"files":[]}' "403"
test_endpoint "Premium user can upload (validation fails)" "POST" "/notes/$PREMIUM_NOTE_ID/attachments" "$PREMIUM_TOKEN" '{"files":[]}' "422"

echo ""

# ===== SUMMARY =====
echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}Test Results${NC}"
echo -e "${BLUE}================================${NC}\n"
echo -e "Total Tests: $((PASS + FAIL))"
echo -e "${GREEN}Passed: $PASS${NC}"
echo -e "${RED}Failed: $FAIL${NC}\n"
