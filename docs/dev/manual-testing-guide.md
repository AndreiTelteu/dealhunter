# Manual Testing Guide

This document provides comprehensive manual testing procedures for the OLX Deal Hunter application. Since the project excludes automated testing frameworks, thorough manual testing is essential for ensuring system reliability and correctness.

## Testing Philosophy

The testing approach focuses on:
- **End-to-end user workflows** - Testing complete user journeys
- **Data integrity** - Ensuring crawled data is accurate and consistent
- **Error resilience** - Verifying graceful handling of failures
- **Performance validation** - Confirming acceptable response times
- **Security verification** - Testing authentication and input validation

## Pre-Testing Setup

### Environment Preparation

1. **Clean Database State:**
```bash
php artisan migrate:fresh --seed
```

2. **Verify System Health:**
```bash
php artisan crawler:test-connection
php artisan config:show | grep -E "(crawler|ai|database)"
```

3. **Create Test Data:**
```bash
# Create test user
php artisan make:user test@example.com --password=testpass123

# Create sample hunted deals
php artisan hunted-deal:create --user=1 --search-term="laptop test" --active
php artisan hunted-deal:create --user=1 --search-term="telefon samsung" --active
```

### Test Data Requirements

- At least 2 test user accounts
- 3-5 hunted deals with different search terms
- Mix of active and inactive hunted deals
- Some existing deals with historical snapshots

## Core Functionality Testing

### 1. Authentication System

#### Registration Flow
**Test Steps:**
1. Navigate to `/register`
2. Test form validation:
   - Empty fields → Should show validation errors
   - Invalid email format → Should show email error
   - Weak password → Should show password requirements
   - Mismatched password confirmation → Should show error
3. Submit valid registration form
4. Verify redirect to dashboard
5. Check database for new user record

**Expected Results:**
- Form validation prevents invalid submissions
- Successful registration creates user record
- User is automatically logged in after registration
- Dashboard shows personalized content

**Test Cases:**
```
✓ Empty form submission shows all required field errors
✓ Invalid email "notanemail" shows format error
✓ Password "123" shows strength requirements
✓ Password confirmation mismatch shows error
✓ Valid form creates user and redirects to dashboard
✓ Duplicate email shows "already exists" error
```

#### Login Flow
**Test Steps:**
1. Navigate to `/login`
2. Test with invalid credentials
3. Test with valid credentials
4. Test "Remember Me" functionality
5. Test logout functionality

**Expected Results:**
- Invalid credentials show clear error message
- Valid credentials redirect to dashboard
- Remember Me extends session duration
- Logout clears session and redirects to login

### 2. Hunted Deals Management

#### Create Hunted Deal
**Test Steps:**
1. Navigate to "Hunted Deals" → "Create New"
2. Test form validation:
   - Empty search term → Should show required error
   - Very long search term (>255 chars) → Should show length error
3. Create hunted deal with valid data
4. Verify it appears in hunted deals list
5. Check database record creation

**Test Cases:**
```
✓ Empty search term shows validation error
✓ Search term over 255 characters shows length error
✓ Valid form creates hunted deal record
✓ New hunted deal appears in list immediately
✓ Default values (is_active=true) are set correctly
✓ Notes field accepts optional text
```

#### Edit Hunted Deal
**Test Steps:**
1. Click "Edit" on existing hunted deal
2. Modify search term, notes, and active status
3. Submit changes
4. Verify changes are reflected in list and database

**Expected Results:**
- Form pre-populates with current values
- Changes are saved correctly
- UI updates immediately reflect changes

#### Delete Hunted Deal
**Test Steps:**
1. Click "Delete" on hunted deal
2. Confirm deletion in modal dialog
3. Verify hunted deal is removed from list
4. Check that associated deals and snapshots are also deleted

**Expected Results:**
- Confirmation modal prevents accidental deletion
- Cascading delete removes all related data
- UI updates to remove deleted item

### 3. Crawling System

#### Manual Crawl Testing

**Dry Run Test:**
```bash
php artisan deals:crawl --dry-run --verbose --hunted-deal=1
```

**Expected Output Pattern:**
```
Starting crawl for hunted deal: laptop test
Connecting to MCP server...
✓ MCP connection established
Navigating to OLX search...
✓ Navigation successful
Extracting listings from page 1...
✓ Found 20 listings on page 1
Extracting listings from page 2...
✓ Found 18 listings on page 2
Processing 38 total listings...
✓ Parsed 38 listings successfully
DRY RUN: Would create 25 new deals
DRY RUN: Would update 13 existing deals
DRY RUN: Would create 38 snapshots
Crawl completed in 45.2 seconds
```

**Real Crawl Test:**
```bash
php artisan deals:crawl --hunted-deal=1 --verbose
```

**Verification Steps:**
1. Check database for new deals:
```sql
SELECT COUNT(*) FROM deals WHERE hunted_deal_id = 1;
```

2. Verify snapshots were created:
```sql
SELECT COUNT(*) FROM deal_snapshots 
WHERE deal_id IN (SELECT id FROM deals WHERE hunted_deal_id = 1);
```

3. Check last_crawled_at timestamp was updated:
```sql
SELECT last_crawled_at FROM hunted_deals WHERE id = 1;
```

#### Data Quality Validation

**Price Parsing Tests:**
```bash
php artisan tinker
```

```php
$parser = app(\App\Services\PriceParserService::class);

// Test Romanian Lei formats
$parser->parsePrice("1.500 lei");        // Expected: 1500.00 RON
$parser->parsePrice("1500 lei");         // Expected: 1500.00 RON
$parser->parsePrice("1.500,50 lei");     // Expected: 1500.50 RON

// Test Euro formats
$parser->parsePrice("€250");             // Expected: ~1237.50 RON
$parser->parsePrice("250 EUR");          // Expected: ~1237.50 RON

// Test special cases
$parser->parsePrice("Negociabil");       // Expected: null
$parser->parsePrice("La cerere");        // Expected: null
```

**External ID Extraction Tests:**
```php
$crawler = app(\App\Services\Crawlers\OlxCrawlerService::class);

// Test URL patterns
$crawler->extractExternalId("https://www.olx.ro/d/oferta/laptop-dell-ID123456.html");
// Expected: "123456"

$crawler->extractExternalId("https://www.olx.ro/d/oferta/telefon-samsung-789012.html");
// Expected: "789012"
```

### 4. AI Classification Testing

#### Connection Test
```bash
php artisan ai:test-classification --test-connection
```

**Expected Output:**
```
Testing AI provider connection...
✓ OpenAI API connection successful
✓ Model gpt-3.5-turbo is available
✓ API key has sufficient credits
AI classification system is ready
```

#### Classification Accuracy Tests

**Working Item Test:**
```bash
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop Dell Latitude E7450" \
  --description="Laptop in stare foarte buna, functional, testat, garantie 6 luni"
```

**Expected Results:**
- `matches_intent`: true (laptop matches search term)
- `likely_working`: true (positive keywords detected)
- `confidence`: > 0.8 (high confidence)

**Broken Item Test:**
```bash
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Laptop pentru piese" \
  --description="Laptop stricat, nu porneste, ecran spart, pentru piese"
```

**Expected Results:**
- `matches_intent`: true (still a laptop)
- `likely_working`: false (broken keywords detected)
- `confidence`: > 0.8 (high confidence)

**Irrelevant Item Test:**
```bash
php artisan ai:test-classification \
  --search-term="laptop" \
  --title="Masina BMW 320d" \
  --description="Masina in stare buna, recent adusa din Germania"
```

**Expected Results:**
- `matches_intent`: false (car doesn't match laptop search)
- `likely_working`: true (car seems functional)
- `confidence`: > 0.7 (moderate to high confidence)

### 5. Web Interface Testing

#### Dashboard Testing
**Test Steps:**
1. Login and navigate to dashboard
2. Verify recent activity section shows latest deals
3. Check hunted deals summary displays correct counts
4. Confirm quick stats are accurate
5. Test responsive design on different screen sizes

**Expected Elements:**
- Recent deals with correct timestamps
- Hunted deals count matches database
- Price drop indicators work correctly
- Mobile layout is usable

#### Deals Listing Testing
**Test Steps:**
1. Navigate to deals list
2. Test pagination with different page sizes
3. Apply various filters:
   - Price drops in last 24h
   - New items filter
   - AI classification filters
   - Price range filters
4. Test sorting options
5. Use search functionality

**Filter Test Cases:**
```
✓ "Price drops" filter shows only deals with price decreases
✓ "New items (24h)" shows deals first seen in last 24 hours
✓ "Matches intent" shows only AI-classified relevant deals
✓ "Likely working" shows only AI-classified working items
✓ Price range filter (1000-2000 RON) shows correct results
✓ Search "Dell" shows only deals with "Dell" in title/description
```

#### Deal Detail Page Testing
**Test Steps:**
1. Click on deal from list
2. Verify all deal information displays correctly
3. Check price history chart functionality
4. Review change timeline
5. Test image gallery
6. Click external link to OLX

**Expected Elements:**
- Current deal information (title, price, description)
- Price history chart with data points
- Timeline showing chronological changes
- Image gallery with thumbnails
- Working external link to OLX listing

### 6. Error Handling Testing

#### Network Error Simulation
**Test Steps:**
1. Start a crawl operation
2. Disconnect internet connection during crawl
3. Observe error handling and logging
4. Reconnect and verify system recovery

**Expected Behavior:**
- Graceful error handling without crashes
- Comprehensive error logging
- System continues with other hunted deals
- Recovery when connection is restored

#### Invalid Data Testing
**Test Steps:**
1. Create hunted deal with search term that returns no results
2. Run crawl and observe behavior
3. Test with search terms containing special characters
4. Verify system handles empty result sets

**Expected Behavior:**
- No crashes when no results found
- Appropriate logging of empty result sets
- System continues with other hunted deals

#### Database Error Testing
**Test Steps:**
1. Temporarily make database read-only
2. Attempt to run crawl
3. Observe error handling
4. Restore database permissions

**Expected Behavior:**
- Database errors are caught and logged
- No data corruption occurs
- System fails gracefully

## Performance Testing

### Response Time Testing

**Page Load Times:**
- Dashboard: < 2 seconds
- Deals list (100 items): < 3 seconds
- Deal detail page: < 1 second
- Hunted deals management: < 1 second

**Crawl Performance:**
- Single hunted deal (3 pages): < 2 minutes
- Memory usage during crawl: < 256MB
- Database queries: < 100ms average

### Load Testing

**Concurrent Operations:**
1. Run multiple crawls simultaneously
2. Use web interface while crawling
3. Monitor system resources
4. Verify no data corruption

**Large Dataset Testing:**
1. Create hunted deals with 500+ results
2. Test pagination performance
3. Verify filtering remains responsive
4. Check memory usage with large datasets

## Security Testing

### Authentication Security
**Test Cases:**
```
✓ Password requirements enforced (min 8 chars, complexity)
✓ Session timeout works correctly (default 2 hours)
✓ CSRF protection prevents form tampering
✓ Password reset functionality works securely
✓ Account lockout after failed login attempts
```

### Input Validation
**Test Cases:**
```
✓ XSS prevention in search terms and notes
✓ SQL injection prevention in all inputs
✓ File upload restrictions (if applicable)
✓ URL validation for external links
✓ HTML sanitization in user inputs
```

## Regression Testing

### After Code Changes
Run this abbreviated test suite after any code changes:

1. **Smoke Tests:**
   - User can login
   - Hunted deal can be created
   - Manual crawl completes successfully
   - Deals display correctly in web interface

2. **Critical Path Tests:**
   - End-to-end crawl workflow
   - AI classification accuracy
   - Data integrity checks
   - Error handling verification

3. **Performance Checks:**
   - Crawl duration within limits
   - Memory usage acceptable
   - Database query performance

## Test Documentation

### Test Results Recording

For each test session, document:
- Date and time of testing
- Environment details (local/staging/production)
- Test cases executed
- Results (pass/fail/partial)
- Issues discovered
- Performance metrics
- Recommendations

### Issue Tracking

When issues are found:
1. Document exact steps to reproduce
2. Include error messages and logs
3. Note environment and configuration
4. Assess severity and impact
5. Track resolution status

### Test Coverage Tracking

Maintain a checklist of tested functionality:
- [ ] User authentication flows
- [ ] Hunted deals CRUD operations
- [ ] Crawling system functionality
- [ ] AI classification accuracy
- [ ] Web interface usability
- [ ] Error handling robustness
- [ ] Performance characteristics
- [ ] Security measures

This manual testing approach ensures comprehensive coverage of the application's functionality while maintaining the project's requirement to exclude automated testing frameworks.