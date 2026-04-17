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

echo -e "${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║     API Test Suite - Finálne Úlohy (All Tasks)        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}\n"

# Helper function
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

    if [[ "$http_code" == "$expected_status"* ]]; then
        echo -e "${GREEN}✓ PASS${NC} (HTTP $http_code)"
        PASS=$((PASS+1))
    else
        echo -e "${RED}✗ FAIL${NC} (Expected $expected_status, got $http_code)"
        FAIL=$((FAIL+1))
    fi
}

# ===== AUTHENTICATION =====
echo -e "${BLUE}AUTHENTICATION${NC}\n"

echo -ne "${YELLOW}Login as regular user${NC} ... "
USER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"user@test.com","password":"password"}')
USER_TOKEN=$(echo "$USER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ OK${NC}\n"

echo -ne "${YELLOW}Login as admin${NC} ... "
ADMIN_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@test.com","password":"password"}')
ADMIN_TOKEN=$(echo "$ADMIN_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ OK${NC}\n"

echo -ne "${YELLOW}Login as other user${NC} ... "
OTHER_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"other@test.com","password":"password"}')
OTHER_TOKEN=$(echo "$OTHER_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ OK${NC}\n"

echo -ne "${YELLOW}Login as premium user${NC} ... "
PREMIUM_LOGIN=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"premium@test.com","password":"password"}')
PREMIUM_TOKEN=$(echo "$PREMIUM_LOGIN" | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo -e "${GREEN}✓ OK${NC}\n"

# ===== CREATE TEST RESOURCES =====
NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Note","body":"This is a test note","status":"draft"}')
NOTE_ID=$(echo "$NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

TASK=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/tasks" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Task","description":"Task description","status":"pending"}')
TASK_ID=$(echo "$TASK" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

# ===== TASK 1: AUTHORIZATION =====
echo -e "${BLUE}TASK 1: AUTHORIZATION${NC}\n"

echo -e "${YELLOW}Notes${NC}"
test_endpoint "  View own note" "GET" "/notes/$NOTE_ID" "$USER_TOKEN" "" "200"
test_endpoint "  View note (other user blocked)" "GET" "/notes/$NOTE_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "  View note (admin bypass)" "GET" "/notes/$NOTE_ID" "$ADMIN_TOKEN" "" "200"
test_endpoint "  Update own note" "PUT" "/notes/$NOTE_ID" "$USER_TOKEN" '{"title":"Updated"}' "200"
test_endpoint "  Update note (other user blocked)" "PUT" "/notes/$NOTE_ID" "$OTHER_TOKEN" '{"title":"X"}' "403"
test_endpoint "  Update note (admin bypass)" "PUT" "/notes/$NOTE_ID" "$ADMIN_TOKEN" '{"title":"Admin"}' "200"

echo -e "\n${YELLOW}Tasks${NC}"
test_endpoint "  View own task" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" "" "200"
test_endpoint "  View task (other user blocked)" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" "" "403"
test_endpoint "  View task (admin bypass)" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID" "$ADMIN_TOKEN" "" "200"
test_endpoint "  Update own task" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$USER_TOKEN" '{"title":"Updated"}' "200"
test_endpoint "  Update task (other user blocked)" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$OTHER_TOKEN" '{"title":"X"}' "403"
test_endpoint "  Update task (admin bypass)" "PUT" "/notes/$NOTE_ID/tasks/$TASK_ID" "$ADMIN_TOKEN" '{"title":"Admin"}' "200"

# ===== TASK 2: COMMENTS =====
echo -e "\n${BLUE}TASK 2: COMMENTS${NC}\n"

echo -e "${YELLOW}Note Comments${NC}"
test_endpoint "  View note comments" "GET" "/notes/$NOTE_ID/comments" "$USER_TOKEN" "" "200"
test_endpoint "  Add comment to note" "POST" "/notes/$NOTE_ID/comments" "$USER_TOKEN" '{"body":"Test comment"}' "201"

COMMENT=$(curl -s -X POST "$BASE_URL/notes/$NOTE_ID/comments" \
  -H "Authorization: Bearer $USER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"body":"Comment to test"}')
COMMENT_ID=$(echo "$COMMENT" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

echo -e "\n${YELLOW}Task Comments${NC}"
test_endpoint "  View task comments" "GET" "/notes/$NOTE_ID/tasks/$TASK_ID/comments" "$USER_TOKEN" "" "200"
test_endpoint "  Add comment to task" "POST" "/notes/$NOTE_ID/tasks/$TASK_ID/comments" "$USER_TOKEN" '{"body":"Task comment"}' "201"

echo -e "\n${YELLOW}Comment Management${NC}"
test_endpoint "  Edit own comment" "PUT" "/comments/$COMMENT_ID" "$USER_TOKEN" '{"body":"Updated"}' "200"
test_endpoint "  Edit comment (other user blocked)" "PUT" "/comments/$COMMENT_ID" "$OTHER_TOKEN" '{"body":"X"}' "403"
test_endpoint "  Delete comment (admin bypass)" "DELETE" "/comments/$COMMENT_ID" "$ADMIN_TOKEN" "" "200"

# ===== TASK 3: PREMIUM =====
echo -e "\n${BLUE}TASK 3: PREMIUM ACCESS${NC}\n"

PREMIUM_NOTE=$(curl -s -X POST "$BASE_URL/notes" \
  -H "Authorization: Bearer $PREMIUM_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Premium Note","body":"Test","status":"draft"}')
PREMIUM_NOTE_ID=$(echo "$PREMIUM_NOTE" | grep -o '"id":[0-9]*' | head -1 | cut -d':' -f2)

echo -e "${YELLOW}File Attachments${NC}"
test_endpoint "  Non-premium user upload (blocked)" "POST" "/notes/$PREMIUM_NOTE_ID/attachments" "$USER_TOKEN" '{"files":[]}' "403"
test_endpoint "  Premium user upload (allowed)" "POST" "/notes/$PREMIUM_NOTE_ID/attachments" "$PREMIUM_TOKEN" '{"files":[]}' "422"

# ===== SUMMARY =====
echo -e "\n${BLUE}╔════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                    TEST RESULTS                        ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════╝${NC}\n"

TOTAL=$((PASS + FAIL))
PERCENTAGE=$((PASS * 100 / TOTAL))

echo -e "Total Tests: ${BLUE}$TOTAL${NC}"
echo -e "Passed: ${GREEN}$PASS${NC}"
echo -e "Failed: ${RED}$FAIL${NC}"
echo -e "Success Rate: ${BLUE}$PERCENTAGE%${NC}\n"

if [ $FAIL -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║            ALL TESTS PASSED ✓                         ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════╝${NC}\n"
fi
