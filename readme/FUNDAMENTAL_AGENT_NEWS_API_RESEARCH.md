# Fundamental Agent News API Integration Research

**Date:** May 14, 2026  
**Status:** Research Complete - Ready for Implementation  
**Phase:** News Data Source Analysis & Architecture Recommendation

---

## 📋 Table of Contents

1. [Session Overview](#session-overview)
2. [Fundamental Agent Infrastructure Status](#fundamental-agent-infrastructure-status)
3. [News API Integration Research](#news-api-integration-research)
4. [TradingAgents Architecture Analysis](#tradingagents-architecture-analysis)
5. [Implementation Recommendations](#implementation-recommendations)
6. [Detailed Implementation Guide](#detailed-implementation-guide)
7. [Next Steps](#next-steps)

---

## Session Overview

### User Request
- **Indonesian:** "dimana bisa mendapatkan news api nya, bagaimana penerapan di laravel trading dashboard, sarankan dlu jangan di jalankan"
- **English:** "Where to get news API, how to apply in Laravel dashboard, suggest first don't run"

### Additional Research
- User requested study of TauricResearch/TradingAgents GitHub repository
- Purpose: Learn how they fetch news for fundamental agent

### Deliverables Generated
1. ✅ Comparison of news API providers
2. ✅ TradingAgents architecture analysis
3. ✅ Implementation recommendations
4. ✅ Code pattern examples
5. ✅ Phase-based implementation plan

---

## Fundamental Agent Infrastructure Status

### ✅ COMPLETED Infrastructure

**Database & Models:**
- `database/migrations/2026_05_14_120635_create_fundamental_analyses_table.php` - Schema with raw_context_json field
- `app/Models/FundamentalAnalysis.php` - Eloquent model with proper casting
- Permission `manage fundamental analyses` - Assigned to analyst, admin, super-admin roles

**Services:**
- `app/Services/FundamentalAnalysisGeneratorService.php` - Generates analysis with GENERATED status
- `app/Services/FundamentalAnalysisPromptService.php` - Builds prompts for OpenClaw agent
- `app/Services/TechnicalContextService.php` - Context for agents

**API Controllers & Routes:**
- `app/Http/Controllers/FundamentalAnalysisController.php` - Full REST API (index, store, show, latest, pending, submitResult)
- `app/Http/Controllers/FundamentalAnalysisPageController.php` - Web page controller
- Routes: `routes/api.php` and `routes/web.php` configured

**Web UI:**
- `resources/views/fundamental/analyses/index.blade.php` - Bootstrap 5 + DataTables dashboard
- Summary cards showing status counts
- Form to generate new analysis
- DataTables table with 11 columns and actions

**Python Runner:**
- `agents/fundamental-agent-runner/fundamental_agent_runner.py` - Structure created, core functions implemented
- `.env.example` - Configuration template
- `requirements.txt` - Dependencies listed
- `README.md` - Documentation

**Navigation:**
- Navbar menu item added: "4. Fundamental Analyses" under Workflow section
- Permission-gated with @can directive

### ⧖ PARTIALLY COMPLETE

- **raw_context_json:** Currently contains empty arrays for:
  - `news_events` - Empty, needs NewsAPI data
  - `economic_calendar` - Empty, needs calendar data
  - `sentiment_data` - Empty, needs sentiment analysis
  - `macro_indicators` - Empty, needs macro data

### ❌ NOT YET STARTED

- News data fetching services
- Sentiment analysis integration
- Economic calendar integration
- Macro indicators integration
- Scheduler for auto-generating analyses
- Manager Agent (Risk Rules, Entry Rules, Manager Decisions)

---

## News API Integration Research

### Option 1: NewsAPI.org

**Profile:**
- General news aggregation from 150,000+ sources worldwide
- Covers 500,000+ developers using it
- Simple JSON REST API

**Pros:**
- ✅ Free tier: 100 requests/day
- ✅ Easy to integrate
- ✅ Global coverage
- ✅ Rich metadata (title, description, url, image, date, content)

**Cons:**
- ❌ Limited to 100 req/day on free tier
- ❌ Not financial-specific
- ❌ Limited sentiment scoring

**Pricing:**
- Free: 100 req/day
- Developer: $29+/month
- Business: Custom pricing

**Best For:** MVP development, general news context

**Link:** https://newsapi.org

---

### Option 2: Alpha Vantage

**Profile:**
- NASDAQ-licensed financial data provider
- Partnered with major exchanges
- Real-time and historical market data

**Pros:**
- ✅ Officially licensed by NASDAQ
- ✅ Financial-specific data
- ✅ Includes technical indicators
- ✅ Economic data included
- ✅ Free tier available

**Cons:**
- ❌ Rate limited (5 req/min on free tier)
- ❌ Requires API key registration
- ❌ Not as extensive news coverage as NewsAPI

**Pricing:**
- Free: 5 req/min, limited data
- Premium: $20+/month

**Best For:** Production financial trading, indicators

**Link:** https://www.alphavantage.co

---

### Option 3: yfinance (Python Library)

**Profile:**
- Free financial data from Yahoo Finance
- No API key required
- Rich news endpoints

**Pros:**
- ✅ Completely free
- ✅ No API key needed
- ✅ Built-in news fetch
- ✅ Financial-specific
- ✅ Used by TradingAgents

**Cons:**
- ❌ Python-based (need HTTP wrapper for PHP)
- ❌ Unofficial (not guaranteed stability)
- ❌ Rate limited by Yahoo

**Pricing:** Free

**Best For:** Free MVP, financial news

---

### Comparison Matrix

| Feature | NewsAPI | Alpha Vantage | yfinance | StockTwits | Reddit |
|---------|---------|---------------|----------|-----------|--------|
| Free Tier | 100/day | 5 req/min | Unlimited | Unlimited | Unlimited |
| API Key | Yes | Yes | No | No | No |
| Financial Data | No | Yes | Yes | Yes | Partial |
| Sentiment | No | Yes | Manual | Yes | Manual |
| News Coverage | Very High | High | Medium | Real-time | Discussion |
| Setup Complexity | Low | Low | Medium | Low | Low |
| Reliability | High | High | Medium | High | High |

---

## TradingAgents Architecture Analysis

### Data Sources Used by TradingAgents

#### 1. Yahoo Finance (yfinance) - FREE ✅

**Location:** `tradingagents/dataflows/yfinance_news.py`

**Implementation:**
```python
import yfinance as yf

def get_news(ticker, start_date, end_date):
    """Fetch ticker-specific + global news"""
    ticker_obj = yf.Ticker(ticker)
    news = ticker_obj.news
    # Returns: title, summary, link, publisher, timestamp
    # Rate limit: ~10 req/min
    # Auth: None required
```

**Data Returned:**
- Ticker-specific news
- Global financial news
- Publisher information
- Timestamps
- Article summaries and links

**Usage:** Primary news source in sentiment analysis

---

#### 2. StockTwits - FREE ✅

**Location:** `tradingagents/dataflows/stocktwits.py`

**Implementation:**
```
Endpoint: api.stocktwits.com/api/2/streams/symbol/{ticker}.json
Auth: None required (public endpoint)
Rate limit: ~10-30 requests/min
Returns: 30 messages per call
```

**Data Returned:**
```json
{
  "messages": [
    {
      "body": "Bullish on AAPL earnings",
      "sentiment": "Bullish",  // User-labeled
      "timestamp": "2026-05-14T10:30:00Z",
      "user": {"id": 123, "username": "trader"},
      "likes": 45
    }
  ]
}
```

**Usage:** Real-time retail trader sentiment, user-labeled bullish/bearish signals

---

#### 3. Reddit - FREE ✅

**Location:** `tradingagents/dataflows/reddit.py`

**Implementation:**
```
Endpoint: reddit.com/r/{subreddit}/search.json?q={ticker}
Auth: None required (public JSON endpoints)
Rate limit: ~10 requests/min per IP
Returns: Community posts
```

**Data Returned:**
```json
{
  "data": {
    "children": [
      {
        "data": {
          "title": "AAPL breaking new highs",
          "selftext": "Post content...",
          "ups": 1250,
          "num_comments": 340,
          "created_utc": 1715699400,
          "subreddit": "stocks"
        }
      }
    ]
  }
}
```

**Subreddits Queried:** Finance-related communities (r/stocks, r/investing, etc.)

**Usage:** Community sentiment, engagement signals (upvotes, comments)

---

#### 4. Alpha Vantage News - PAID/FREEMIUM 💰

**Location:** `tradingagents/dataflows/alpha_vantage_news.py`

**Implementation:**
```
Base: https://www.alphavantage.co/query
Endpoint: NEWS_SENTIMENT
Auth: API key required (free tier: 5 req/min)
Pricing: Free + paid tiers
```

**Data Returned:**
- Professional sentiment scores (-100 to +100)
- Multiple news outlets
- Market-impact scoring
- Insider transaction data
- Coverage: Stocks, crypto, forex, commodities

**Usage:** Enterprise-level sentiment when budget available

---

### Architecture Pattern: Pre-Fetching Strategy

**Key Insight from TradingAgents:**

```
Sentiment Analyst Workflow:
┌─────────────────────────────────────────────┐
│ 1. Fetch ALL data BEFORE calling LLM        │
│    - yfinance news                          │
│    - StockTwits messages                    │
│    - Reddit posts                           │
│    - (Optional) Alpha Vantage sentiment     │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│ 2. Format as structured Markdown blocks     │
│    - Clear section headers                  │
│    - Organized data structure               │
│    - Consistent format                      │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│ 3. Inject into system prompt                │
│    - Included in context                    │
│    - Available to LLM                       │
│    - No need for tool calls                 │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│ 4. LLM analyzes pre-fetched data            │
│    - All info already in context            │
│    - Faster response                        │
│    - Prevents hallucination                 │
└─────────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────────┐
│ 5. Return decision JSON                     │
│    - sentiment_bias: bullish/bearish/neutral│
│    - risk_level: low/medium/high            │
│    - confidence: 0-100                      │
│    - reason_summary: text explanation       │
└─────────────────────────────────────────────┘
```

**Advantages of This Approach:**

✅ **Prevents Hallucination:** LLM can't fabricate news that doesn't exist
✅ **Token Efficient:** Data pre-formatted, LLM doesn't waste tokens parsing
✅ **Consistent Format:** All data injected in same format
✅ **Reliable:** Fails gracefully (empty data vs API error)
✅ **Faster:** No tool calls during analysis
✅ **Debuggable:** Can inspect exact data given to LLM

---

### Error Handling Pattern

**TradingAgents Strategy:**

```python
def fetch_news(ticker):
    try:
        # Attempt fetch
        data = api.get_news(ticker)
        return format_data(data)
    except Exception as e:
        # Never raise exception
        # Return graceful placeholder
        return """
## News (Unavailable)
Could not fetch news data. 
This may be due to:
- Network connectivity issue
- API rate limit exceeded
- Service temporarily unavailable
        """
```

**Result:** Analysis never breaks, LLM always has something to work with

---

## Implementation Recommendations

### Recommended Approach: Hybrid Multi-Source

**For Laravel Trading Dashboard:**

```
Phase 1 - MVP (FREE TIER):
├─ yfinance (free financial news)
├─ StockTwits (free sentiment)
├─ Reddit (free discussion)
└─ Total setup time: 2-3 hours

Phase 2 - Enhancement:
├─ Add caching layer (Redis)
├─ Vendor routing abstraction
├─ Economic calendar API
└─ Setup time: 4-6 hours

Phase 3 - Production Ready:
├─ Alpha Vantage integration (if budget available)
├─ Macro indicators
├─ Insider data
├─ Risk event alerts
└─ Setup time: 8-12 hours
```

### Why This Approach?

1. **Cost Effective:** Start free, upgrade as needed
2. **Low Risk:** Test with free sources first
3. **Flexible:** Swap providers without code changes
4. **Scalable:** Easy to add new sources
5. **Proven:** TradingAgents uses same pattern

---

## Detailed Implementation Guide

### Step 1: Create NewsDataFetcher Service

**File:** `app/Services/NewsDataFetcher.php`

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Exception;

class NewsDataFetcher
{
    private const CACHE_DURATION = 3600; // 1 hour
    private const YAHOO_FINANCE_TIMEOUT = 10;
    private const STOCKTWITS_TIMEOUT = 10;
    private const REDDIT_TIMEOUT = 10;

    /**
     * Fetch all news data for a symbol
     * 
     * Returns structured array ready for LLM injection
     */
    public function fetchSymbolNews(string $symbol, string $timeframeScope = 'daily'): array
    {
        $cacheKey = "news_{$symbol}_{$timeframeScope}";
        
        // Return cached data if available
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $newsData = [
            'news_events' => $this->fetchYahooFinanceNews($symbol),
            'sentiment_data' => [
                'stocktwits' => $this->fetchStockTwitsData($symbol),
                'reddit' => $this->fetchRedditData($symbol),
            ],
            'timestamp' => now()->toIso8601String(),
            'sources' => 'yfinance, stocktwits, reddit',
        ];

        // Cache the results
        Cache::put($cacheKey, $newsData, self::CACHE_DURATION);

        return $newsData;
    }

    /**
     * Fetch news from Yahoo Finance
     */
    private function fetchYahooFinanceNews(string $symbol): array
    {
        try {
            // Option 1: Using Python via subprocess (if Python installed)
            $output = shell_exec("python3 -c \"import yfinance as yf; import json; print(json.dumps(yf.Ticker('$symbol').news[:10]))\"");
            
            if ($output) {
                return json_decode($output, true) ?? [];
            }

            // Option 2: Using REST API (finnhub or similar)
            // This is a fallback if Python not available
            return $this->fetchNewsViaREST($symbol);
        } catch (Exception $e) {
            return $this->getErrorPlaceholder('Yahoo Finance');
        }
    }

    /**
     * Fetch sentiment data from StockTwits (Public API - No Auth)
     * Endpoint: api.stocktwits.com/api/2/streams/symbol/{ticker}.json
     */
    private function fetchStockTwitsData(string $symbol): array
    {
        try {
            $response = Http::timeout(self::STOCKTWITS_TIMEOUT)
                ->get("https://api.stocktwits.com/api/2/streams/symbol/{$symbol}.json");

            if (!$response->successful()) {
                return $this->getErrorPlaceholder('StockTwits');
            }

            $data = $response->json();
            $messages = $data['messages'] ?? [];

            // Format for LLM consumption
            return [
                'count' => count($messages),
                'summary' => $this->summarizeStockTwitsMessages($messages),
                'raw_messages' => array_slice($messages, 0, 5), // Top 5
            ];
        } catch (Exception $e) {
            return $this->getErrorPlaceholder('StockTwits');
        }
    }

    /**
     * Fetch discussion data from Reddit (Public API - No Auth)
     * Endpoint: reddit.com/r/{subreddit}/search.json?q={ticker}
     */
    private function fetchRedditData(string $symbol): array
    {
        try {
            $subreddits = ['stocks', 'investing', 'wallstreetbets', 'finance'];
            $allPosts = [];

            foreach ($subreddits as $subreddit) {
                $response = Http::timeout(self::REDDIT_TIMEOUT)
                    ->withHeaders(['User-Agent' => 'TradingDashboard/1.0'])
                    ->get("https://reddit.com/r/{$subreddit}/search.json", [
                        'q' => $symbol,
                        'restrict_sr' => 'on',
                        'limit' => 10,
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $posts = $data['data']['children'] ?? [];
                    $allPosts = array_merge($allPosts, $posts);
                }
            }

            return [
                'count' => count($allPosts),
                'summary' => $this->summarizeRedditPosts($allPosts),
                'top_posts' => array_slice($allPosts, 0, 5),
            ];
        } catch (Exception $e) {
            return $this->getErrorPlaceholder('Reddit');
        }
    }

    /**
     * Summarize StockTwits messages for LLM
     */
    private function summarizeStockTwitsMessages(array $messages): string
    {
        $bullishCount = 0;
        $bearishCount = 0;

        foreach ($messages as $msg) {
            if (($msg['sentiment'] ?? null) === 'Bullish') {
                $bullishCount++;
            } elseif (($msg['sentiment'] ?? null) === 'Bearish') {
                $bearishCount++;
            }
        }

        $total = count($messages);
        $bullishPercent = $total > 0 ? round(($bullishCount / $total) * 100) : 0;
        $bearishPercent = $total > 0 ? round(($bearishCount / $total) * 100) : 0;

        return "StockTwits sentiment: {$bullishPercent}% bullish, {$bearishPercent}% bearish, from {$total} messages";
    }

    /**
     * Summarize Reddit posts for LLM
     */
    private function summarizeRedditPosts(array $posts): string
    {
        $totalUpvotes = 0;
        $totalComments = 0;

        foreach ($posts as $post) {
            $data = $post['data'] ?? [];
            $totalUpvotes += $data['ups'] ?? 0;
            $totalComments += $data['num_comments'] ?? 0;
        }

        $postCount = count($posts);
        $avgUpvotes = $postCount > 0 ? round($totalUpvotes / $postCount) : 0;

        return "Reddit: {$postCount} posts, avg {$avgUpvotes} upvotes, {$totalComments} total comments";
    }

    /**
     * Fallback: Fetch via REST API if Python not available
     */
    private function fetchNewsViaREST(string $symbol): array
    {
        try {
            // Using NewsAPI.org (if configured)
            $apiKey = config('services.newsapi.key');
            if (!$apiKey) {
                return [];
            }

            $response = Http::timeout(self::YAHOO_FINANCE_TIMEOUT)
                ->get('https://newsapi.org/v2/everything', [
                    'q' => $symbol,
                    'sortBy' => 'publishedAt',
                    'apiKey' => $apiKey,
                    'pageSize' => 10,
                ]);

            return $response->json()['articles'] ?? [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Return error placeholder (graceful degradation)
     */
    private function getErrorPlaceholder(string $source): array
    {
        return [
            'error' => true,
            'source' => $source,
            'message' => "{$source} data temporarily unavailable",
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
```

### Step 2: Update FundamentalAnalysisGeneratorService

**File:** `app/Services/FundamentalAnalysisGeneratorService.php`

```php
<?php

namespace App\Services;

use App\Models\FundamentalAnalysis;
use Illuminate\Support\Str;

class FundamentalAnalysisGeneratorService
{
    private NewsDataFetcher $newsDataFetcher;

    public function __construct(NewsDataFetcher $newsDataFetcher)
    {
        $this->newsDataFetcher = $newsDataFetcher;
    }

    /**
     * Generate fundamental analysis for a symbol
     */
    public function generateForSymbol(string $symbol, string $timeframeScope = null): FundamentalAnalysis
    {
        // Validate symbol
        if (empty($symbol)) {
            throw new \InvalidArgumentException('Symbol cannot be empty');
        }

        // Default timeframe scope
        $timeframeScope = $timeframeScope ?? 'daily';

        // Fetch news data
        $newsData = $this->newsDataFetcher->fetchSymbolNews($symbol, $timeframeScope);

        // Create analysis
        $analysis = new FundamentalAnalysis([
            'analysis_uuid' => Str::uuid(),
            'symbol' => strtoupper($symbol),
            'timeframe_scope' => $timeframeScope,
            'status' => 'GENERATED',
            'raw_context_json' => [
                'news_events' => $newsData['news_events'] ?? [],
                'sentiment_data' => $newsData['sentiment_data'] ?? [],
                'economic_calendar' => [],
                'macro_indicators' => [],
                'metadata' => [
                    'data_sources' => $newsData['sources'] ?? 'unknown',
                    'fetch_timestamp' => $newsData['timestamp'] ?? now()->toIso8601String(),
                ],
            ],
        ]);

        $analysis->save();

        return $analysis;
    }
}
```

### Step 3: Format News Data for LLM Prompt

**File:** `app/Services/FundamentalAnalysisPromptService.php` (Update)

```php
<?php

namespace App\Services;

use App\Models\FundamentalAnalysis;

class FundamentalAnalysisPromptService
{
    public function buildPrompt(FundamentalAnalysis $analysis): string
    {
        $context = $analysis->raw_context_json;

        return <<<PROMPT
You are a Fundamental Analysis Expert for trading. Analyze the following fundamental data and provide your assessment.

## Market Context

**Symbol:** {$analysis->symbol}
**Timeframe:** {$analysis->timeframe_scope}
**Analysis Date:** {$analysis->created_at}

## News Events

{$this->formatNewsEvents($context)}

## Sentiment Analysis

{$this->formatSentimentData($context)}

## Economic Calendar

{$this->formatEconomicCalendar($context)}

## Macro Indicators

{$this->formatMacroIndicators($context)}

---

Based on the above information, provide your fundamental analysis in the following JSON format:

```json
{
  "fundamental_bias": "bullish|bearish|neutral",
  "news_risk_level": "low|medium|high",
  "sentiment_bias": "bullish|bearish|neutral",
  "avoid_trade": false,
  "confidence": 75,
  "reason_summary": "Brief summary of your analysis",
  "reasons_json": {
    "bullish_factors": ["factor1", "factor2"],
    "bearish_factors": ["factor1"],
    "risk_factors": ["risk1", "risk2"]
  }
}
```

PROMPT;
    }

    private function formatNewsEvents(array $context): string
    {
        $newsEvents = $context['news_events'] ?? [];

        if (empty($newsEvents)) {
            return "No recent news events available.";
        }

        if (isset($newsEvents['error']) && $newsEvents['error']) {
            return "News data: {$newsEvents['message']}";
        }

        $formatted = "";
        foreach (array_slice($newsEvents, 0, 5) as $news) {
            if (is_array($news)) {
                $formatted .= "- **{$news['title'] ?? 'Untitled'}**: {$news['summary'] ?? ''}\n";
                $formatted .= "  Source: {$news['source'] ?? 'Unknown'} | Published: {$news['publishedAt'] ?? ''}\n\n";
            }
        }

        return $formatted ?: "News events data format unrecognized.";
    }

    private function formatSentimentData(array $context): string
    {
        $sentiment = $context['sentiment_data'] ?? [];

        $formatted = "";

        // StockTwits
        if (isset($sentiment['stocktwits'])) {
            $st = $sentiment['stocktwits'];
            if (!($st['error'] ?? false)) {
                $formatted .= "### StockTwits (Retail Trader Sentiment)\n";
                $formatted .= $st['summary'] ?? "No summary available" . "\n\n";
            }
        }

        // Reddit
        if (isset($sentiment['reddit'])) {
            $rd = $sentiment['reddit'];
            if (!($rd['error'] ?? false)) {
                $formatted .= "### Reddit (Community Discussion)\n";
                $formatted .= $rd['summary'] ?? "No summary available" . "\n\n";
            }
        }

        return $formatted ?: "Sentiment data unavailable.";
    }

    private function formatEconomicCalendar(array $context): string
    {
        $events = $context['economic_calendar'] ?? [];
        return !empty($events) 
            ? "## Economic Events\n" . json_encode($events, JSON_PRETTY_PRINT)
            : "Economic calendar data not yet integrated.";
    }

    private function formatMacroIndicators(array $context): string
    {
        $indicators = $context['macro_indicators'] ?? [];
        return !empty($indicators)
            ? "## Macro Indicators\n" . json_encode($indicators, JSON_PRETTY_PRINT)
            : "Macro indicators data not yet integrated.";
    }
}
```

### Step 4: Configuration Setup

**File:** `config/services.php` (Add/Update)

```php
'newsapi' => [
    'key' => env('NEWSAPI_KEY'),
    'base_url' => 'https://newsapi.org/v2',
],

'fundamental' => [
    'cache_duration' => env('FUNDAMENTAL_CACHE_DURATION', 3600),
    'data_sources' => explode(',', env('FUNDAMENTAL_DATA_SOURCES', 'yfinance,stocktwits,reddit')),
],
```

**File:** `.env` (Add)

```env
# News API Configuration
NEWSAPI_KEY=your_newsapi_key_here  # Optional, only for REST fallback

# Fundamental Analysis Config
FUNDAMENTAL_CACHE_DURATION=3600
FUNDAMENTAL_DATA_SOURCES=yfinance,stocktwits,reddit
```

### Step 5: Dependency Injection

**File:** `app/Providers/AppServiceProvider.php` (Update)

```php
public function register()
{
    $this->app->singleton(NewsDataFetcher::class, function ($app) {
        return new NewsDataFetcher();
    });

    $this->app->singleton(FundamentalAnalysisGeneratorService::class, function ($app) {
        return new FundamentalAnalysisGeneratorService(
            $app->make(NewsDataFetcher::class)
        );
    });
}
```

---

## Implementation Phases

### Phase 1: MVP - Free Data Sources (Week 1)

**Objective:** Get end-to-end flow working with free data

**Tasks:**
1. ✅ Create `NewsDataFetcher` service
2. ✅ Update `FundamentalAnalysisGeneratorService` to fetch news
3. ✅ Update `FundamentalAnalysisPromptService` to format news for LLM
4. ✅ Test manual analysis generation: `php artisan tinker`
   ```php
   $service = app(FundamentalAnalysisGeneratorService::class);
   $analysis = $service->generateForSymbol('AAPL', 'daily');
   echo json_encode($analysis->raw_context_json, JSON_PRETTY_PRINT);
   ```
5. ✅ Test API endpoint: `POST /api/fundamental-analyses`
6. ✅ Test Python runner: `python3 agents/fundamental-agent-runner/fundamental_agent_runner.py --limit 1`

**Deliverables:**
- Raw news data populated in `raw_context_json`
- Manual analysis generation working
- API integration working
- Python runner able to fetch and process analyses

**Time:** 4-6 hours

---

### Phase 2: Enhancement - Caching & Vendors (Week 2)

**Objective:** Optimize performance and add vendor flexibility

**Tasks:**
1. ✅ Add Redis caching for news data
2. ✅ Create vendor abstraction (swap providers)
3. ✅ Add economic calendar API (Optional: IEX Cloud, Trading Economics)
4. ✅ Add macro indicators (Optional: Alpha Vantage)
5. ✅ Setup scheduler command: `php artisan fundamental-analysis:generate-all`
6. ✅ Monitor API usage and rate limits

**Deliverables:**
- Reduced API calls via caching
- Flexibility to swap news providers
- Economic events in analysis
- Auto-generation via scheduler

**Time:** 6-8 hours

---

### Phase 3: Production Ready (Week 3+)

**Objective:** Full production deployment

**Tasks:**
1. ✅ Add Alpha Vantage integration (if budget approved)
2. ✅ Insider transaction detection
3. ✅ Risk event alerts
4. ✅ Sentiment trend analysis (multi-day)
5. ✅ Performance optimization
6. ✅ Error monitoring and alerting
7. ✅ Load testing

**Deliverables:**
- Production-grade news pipeline
- Enterprise-level data coverage
- Monitoring and alerting

**Time:** 12+ hours

---

## Next Steps

### Immediate Actions (Today/Tomorrow)

1. **Review this document** - Understand architecture
2. **Check environment** - Do we have Python installed?
   ```bash
   python3 --version
   pip3 list | grep yfinance
   ```
3. **Plan database migration** - Verify migrations ran
4. **Start Phase 1** - Create NewsDataFetcher service

### Before Starting Implementation

1. **Check if Python + yfinance available:**
   ```powershell
   python3 -c "import yfinance; print(yfinance.__version__)"
   ```

2. **If NOT available, options:**
   - Install Python 3.x
   - Use REST API alternative (NewsAPI.org)
   - Use manual HTTP requests to public endpoints

3. **Register API keys (optional for Phase 1):**
   - NewsAPI.org: https://newsapi.org/register
   - Alpha Vantage: https://www.alphavantage.co/api

### Testing Strategy

**Phase 1 Testing:**

```bash
# Test 1: Verify NewsDataFetcher works
php artisan tinker
>>> $service = app(App\Services\NewsDataFetcher::class);
>>> $news = $service->fetchSymbolNews('AAPL');
>>> print_r($news);

# Test 2: Verify FundamentalAnalysisGeneratorService works
>>> $genService = app(App\Services\FundamentalAnalysisGeneratorService::class);
>>> $analysis = $genService->generateForSymbol('AAPL');
>>> print_r($analysis->raw_context_json);

# Test 3: Verify API endpoint
POST http://localhost:8000/api/fundamental-analyses
{
  "symbol": "AAPL",
  "timeframe_scope": "daily"
}

# Test 4: Verify Python runner
python3 agents/fundamental-agent-runner/fundamental_agent_runner.py --limit 1 --dry-run
```

### Debugging Checklist

If something doesn't work:

1. **Check cache:** `php artisan cache:clear`
2. **Check migrations:** `php artisan migrate:status`
3. **Check services:** `php artisan tinker` → try each service
4. **Check logs:** `tail -f storage/logs/laravel.log`
5. **Check permissions:** Verify `manage fundamental analyses` role

---

## Summary: Key Takeaways

### What We Learned from TradingAgents

| Concept | Implementation |
|---------|----------------|
| **Data Sources** | Free first (yfinance, StockTwits, Reddit) |
| **Architecture** | Pre-fetch data, inject into prompt |
| **Error Handling** | Graceful degradation (never fail) |
| **Performance** | Cache results, avoid repeated calls |
| **Flexibility** | Config-driven vendor routing |
| **Scale** | Starts small, grows incrementally |

### Recommended Implementation Path

```
Week 1: MVP (yfinance + StockTwits + Reddit)
  ├─ NewsDataFetcher service
  ├─ Integration with FundamentalAnalysisGeneratorService
  ├─ Format data for LLM prompts
  └─ End-to-end testing

Week 2: Enhancement (Caching + Vendors)
  ├─ Redis caching
  ├─ Vendor abstraction
  ├─ Scheduler command
  └─ Performance monitoring

Week 3+: Production (Economic Calendar + Macro Data)
  ├─ Economic indicators
  ├─ Macro data feeds
  ├─ Alpha Vantage optional
  └─ Full monitoring & alerting
```

### Estimated Effort

| Phase | Effort | Risk | Value |
|-------|--------|------|-------|
| **Phase 1** | 4-6 hrs | Low | MVP working |
| **Phase 2** | 6-8 hrs | Low | Optimized |
| **Phase 3** | 12+ hrs | Medium | Production |

---

## Questions to Answer Before Implementation

1. **Python Available?** Is Python 3.x + yfinance installed?
2. **NewsAPI Budget?** Will we use NewsAPI.org free tier or REST fallback?
3. **Economic Calendar?** Do we need macro data for Phase 1?
4. **Alpha Vantage?** Is there budget for paid API?
5. **Testing?** How will we validate news data quality?
6. **Monitoring?** How will we track API rate limits?

---

## Useful References

**Documentation Links:**
- yfinance: https://github.com/ranaroussi/yfinance
- StockTwits API: https://developers.stocktwits.com/
- Reddit JSON: https://reddit.com/dev/api
- NewsAPI: https://newsapi.org/docs
- Alpha Vantage: https://www.alphavantage.co/documentation/
- TradingAgents: https://github.com/TauricResearch/TradingAgents

**Related Files in This Project:**
- [app/Services/FundamentalAnalysisGeneratorService.php](../app/Services/FundamentalAnalysisGeneratorService.php)
- [app/Services/FundamentalAnalysisPromptService.php](../app/Services/FundamentalAnalysisPromptService.php)
- [app/Http/Controllers/FundamentalAnalysisController.php](../app/Http/Controllers/FundamentalAnalysisController.php)
- [agents/fundamental-agent-runner/fundamental_agent_runner.py](../agents/fundamental-agent-runner/fundamental_agent_runner.py)

---

**Document Generated:** May 14, 2026  
**Status:** Ready for Review and Implementation  
**Next Review:** After Phase 1 Implementation
