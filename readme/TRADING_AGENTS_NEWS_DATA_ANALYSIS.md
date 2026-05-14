# TradingAgents News Data Fetching Architecture
## Comprehensive Analysis for Laravel Trading Dashboard Implementation

**Repository:** https://github.com/TauricResearch/TradingAgents  
**Analysis Date:** May 2026  
**Focus:** News Data Acquisition Pipeline for Fundamental Analysis

---

## Executive Summary

TauricResearch/TradingAgents implements a multi-source news data pipeline with **4 primary data sources** and an intelligent **vendor routing system** that allows switching implementations without code changes. The architecture emphasizes:
- **Graceful degradation** (returns placeholders rather than failing)
- **No LLM fabrication risk** (pre-fetches all data before LLM invocation)
- **Flexible vendor switching** (yfinance vs Alpha Vantage via config)
- **Rate-limit aware** (retry logic, request delays)

---

## Data Sources & APIs

### 1. **Yahoo Finance News** (via yfinance)
**Status:** ✅ **FREE, No API Key Required**

**Files:**
- `tradingagents/dataflows/yfinance_news.py`
- `tradingagents/dataflows/stockstats_utils.py` (retry logic)

**Implementation Details:**
```python
# Ticker-specific news
stock = yf.Ticker(ticker)
news = stock.get_news(count=article_limit)

# Global/macro news (Search API)
search = yf.Search(
    query="Federal Reserve interest rates",
    news_count=50,
    enable_fuzzy_query=True
)
```

**API Endpoints:**
- **Ticker News:** `yfinance.Ticker(ticker).get_news()`
- **Global News:** `yfinance.Search(query).news` - searches multiple predefined queries like:
  - "Federal Reserve interest rates inflation"
  - "S&P 500 earnings GDP economic outlook"
  - "geopolitical risk trade war sanctions"
  - "ECB Bank of England BOJ central bank policy"
  - "oil commodities supply chain energy"

**Data Structure Returned:**
- **Nested Format (preferred):** JSON with `content` object containing:
  - `title` - article headline
  - `summary` - snippet/abstract
  - `provider.displayName` - news outlet (e.g., "Reuters", "Bloomberg")
  - `canonicalUrl` or `clickThroughUrl` - article link
  - `pubDate` - ISO timestamp with timezone

- **Flat Format (fallback):** Simple dict with `title`, `summary`, `publisher`, `link`

**Processing:**
- **Date Filtering:** Extracts articles published between `start_date` and `end_date`
- **Deduplication:** By title to prevent duplicate articles
- **Look-ahead Guard:** Removes articles published after current date (prevents training on future data)
- **Limit:** Configurable, default 100 articles max per request

**Output Format:** Markdown-formatted string
```markdown
## AAPL News, from 2026-05-07 to 2026-05-14:

### Apple Stock Climbs on AI Announcements (source: Reuters)
Apple stock surged 2.4% today following announcement of new AI integration features.
Link: https://example.com/article

### AAPL Q3 Earnings Guidance Raised (source: Bloomberg)
...
```

**Rate Limiting:**
- Uses `yf_retry()` wrapper with exponential backoff (3 max retries, 2s base delay)
- Catches `YFRateLimitError` on HTTP 429 responses
- Delay formula: `2s * 2^attempt`

**Pros:**
- ✅ No authentication required
- ✅ Free tier sufficient for single-agent runs
- ✅ Rich metadata (timestamps, providers, summaries)
- ✅ Global news via predefined search queries

**Cons:**
- ⚠️ Rate-limited (~10 requests/min per IP observed)
- ⚠️ Search quality depends on query tuning
- ⚠️ No ticker sentiment scoring

---

### 2. **Alpha Vantage News Sentiment API**
**Status:** 💰 **PAID/FREEMIUM, API Key Required**

**Files:**
- `tradingagents/dataflows/alpha_vantage_news.py`
- `tradingagents/dataflows/alpha_vantage_common.py`

**Implementation Details:**
```python
params = {
    "tickers": ticker,
    "time_from": "20260507T0000",  # YYYYMMDDTHHMM format
    "time_to": "20260514T0000",
    "limit": 50
}
response = _make_api_request("NEWS_SENTIMENT", params)

# Global news
params = {
    "topics": "financial_markets,economy_macro,economy_monetary",
    "time_from": "20260507T0000",
    "time_to": "20260514T0000",
    "limit": 50
}
```

**API Endpoints:**
- **Ticker News:** `NEWS_SENTIMENT` function with ticker filtering
- **Global News:** `NEWS_SENTIMENT` function with topic filtering
- **Insider Transactions:** `INSIDER_TRANSACTIONS` function
- **Base URL:** `https://www.alphavantage.co/query`

**Data Structure Returned:**
- JSON response with:
  - `feed[]` array of articles
  - `title`, `summary`, `url`
  - `time_published` - ISO timestamp
  - `sentiment_score` - numerical sentiment (-1.0 to +1.0)
  - `relevant_tickers[]` - extracted ticker mentions
  - `source` - news source

**Processing:**
- **Date Filtering:** Via `time_from`/`time_to` parameters (server-side)
- **Topic Filtering:** Server-side for global news
- **Sentiment Scoring:** Built-in (no client-side calculation needed)

**Output Format:** JSON string (not pre-formatted markdown)

**API Key:**
- **Environment Variable:** `ALPHA_VANTAGE_API_KEY`
- **Validation:** Raises `ValueError` if not set

**Rate Limiting:**
- Detects rate limits via "rate limit exceeded" error messages in JSON response
- Raises `AlphaVantageRateLimitError` custom exception
- **Free tier:** 5 API calls per minute, 500 per day
- **Paid tier:** Higher limits depending on subscription

**Authentication:**
```python
api_params = {
    "function": function_name,
    "apikey": api_key,  # Passed in every request
    "source": "trading_agents",
}
# Optional entitlement parameter for premium subscriptions
```

**Pros:**
- ✅ Built-in sentiment scoring
- ✅ Server-side date filtering (no client processing)
- ✅ Covers crypto, forex, commodities (not just stocks)
- ✅ Insider transactions included
- ✅ Higher quality data than free alternatives

**Cons:**
- ❌ Requires API key (free tier: 5 calls/min, 500/day)
- ❌ Paid subscription needed for heavy usage
- ❌ API response is raw JSON, needs parsing
- ❌ Limited to Alpha Vantage's news partners (smaller than yfinance)

---

### 3. **StockTwits Cashtag Stream**
**Status:** ✅ **FREE, No API Key Required**

**Files:**
- `tradingagents/dataflows/stocktwits.py`

**Implementation Details:**
```python
url = f"https://api.stocktwits.com/api/2/streams/symbol/{ticker}.json"
response = urlopen(request, timeout=10.0)
messages = response.json().get("messages", [])
```

**API Endpoint:**
- **Stream:** `https://api.stocktwits.com/api/2/streams/symbol/{TICKER}.json`
- **No authentication required**
- **No API key needed**

**Data Structure Returned:**
- Array of message objects containing:
  - `body` - user message text (up to 280 chars, truncated if longer)
  - `user.username` - StockTwits username
  - `created_at` - timestamp
  - `entities.sentiment.basic` - "Bullish", "Bearish", or null
  - `id` - unique message ID

**Processing:**
```python
for message in messages[:30]:  # Default limit of 30 messages
    sentiment = message.get("entities", {}).get("sentiment", {}).get("basic")
    body = message.get("body", "").replace("\n", " ").strip()
    if len(body) > 280:
        body = body[:280] + "…"
    
    # Aggregate sentiment tags
    if sentiment == "Bullish":
        bullish_count += 1
    elif sentiment == "Bearish":
        bearish_count += 1
```

**Output Format:**
```
StockTwits — 30 messages mentioning $AAPL:

[Bullish] User1: "AAPL about to break out, massive upside potential"
[Bullish] User2: "Strong earnings beat, buying more"
[Bearish] User3: "Valuation is stretched, time to take profit"
[unlabeled] User4: "Watching the $155 resistance level"

Summary: Bullish 15 | Bearish 5 | Unlabeled 10
```

**Rate Limiting:**
- Public endpoint: ~10 requests per minute per IP
- Uses timeout=10 seconds per request
- Gracefully returns placeholder string on timeout

**Error Handling:**
```python
try:
    with urlopen(request, timeout=10.0) as resp:
        data = json.loads(resp.read())
except (HTTPError, URLError, json.JSONDecodeError, TimeoutError):
    return f"<stocktwits unavailable: {error_type}>"  # No exception raised
```

**User Agent:**
```
User-Agent: tradingagents/0.2 (+https://github.com/TauricResearch/TradingAgents)
Accept: application/json
```

**Pros:**
- ✅ Free, no API key
- ✅ Built-in sentiment labels (Bullish/Bearish)
- ✅ Real-time retail trader sentiment
- ✅ User participation visible (helps weight signal)

**Cons:**
- ⚠️ Rate limited to ~10 req/min public
- ⚠️ Retail-only signal (may be noisy)
- ⚠️ Limited message history depth
- ⚠️ No historical data available

---

### 4. **Reddit Public API (JSON Endpoints)**
**Status:** ✅ **FREE, No API Key Required**

**Files:**
- `tradingagents/dataflows/reddit.py`

**Implementation Details:**
```python
url = f"https://www.reddit.com/r/{sub}/search.json"
qs = urlencode({
    "q": ticker,
    "restrict_sr": "on",  # Search within subreddit only
    "sort": "new",
    "t": "week",  # Last 7 days
    "limit": 5  # Per subreddit
})
response = urlopen(request, timeout=10.0)
posts = response.json().get("data", {}).get("children", [])
```

**API Endpoint:**
- **Search:** `https://www.reddit.com/r/{SUBREDDIT}/search.json?q={TICKER}&restrict_sr=on&sort=new&t=week&limit={LIMIT}`
- **No authentication required**
- **No API key needed**

**Default Subreddits (ordered by signal density):**
1. `r/wallstreetbets` - high volume, noisy, contrarian/exuberant
2. `r/stocks` - moderate volume, more signal
3. `r/investing` - lower volume, long-term focused

**Query Parameters:**
- `q` - search query (ticker symbol)
- `restrict_sr=on` - search only in specified subreddit
- `sort=new` - newest posts first
- `t=week` - last 7 days only
- `limit` - posts per subreddit (default 5)

**Data Structure Returned:**
- Array of post objects containing:
  - `title` - post headline
  - `selftext` - post body text (truncated if > 240 chars)
  - `score` - upvotes - downvotes
  - `num_comments` - comment count
  - `created_utc` - Unix timestamp

**Processing:**
```python
for post in posts:
    title = post.get("title", "").replace("\n", " ").strip()
    score = post.get("score", 0)
    comments = post.get("num_comments", 0)
    created = time.gmtime(post.get("created_utc", 0))
    created_str = time.strftime("%Y-%m-%d", created)
    selftext = post.get("selftext", "").replace("\n", " ").strip()
    if len(selftext) > 240:
        selftext = selftext[:240] + "…"
```

**Output Format:**
```markdown
r/wallstreetbets — 5 recent posts mentioning AAPL:

[2026-05-14 · 1240↑ · 245c] AAPL breaking out, loading up on calls
  body excerpt: Just bought 50 call contracts for June expiration. This thing is going to moon...

[2026-05-13 ·  324↑ ·  89c] Apple earnings beat, $170 PT incoming
  body excerpt: Strong guidance and AI integration announcement...
```

**Rate Limiting:**
- Public endpoint: ~10 requests per minute per IP
- Framework uses `inter_request_delay=0.4s` between subreddit requests
- Timeout per request: 10 seconds

**User Agent:**
```
User-Agent: tradingagents/0.2 (+https://github.com/TauricResearch/TradingAgents)
Accept: application/json
```

**Error Handling:**
```python
try:
    with urlopen(request, timeout=10.0) as resp:
        payload = json.loads(resp.read())
except (HTTPError, URLError, json.JSONDecodeError, TimeoutError):
    return []  # Empty list, graceful degradation
```

**Pros:**
- ✅ Free, no API key
- ✅ Community sentiment visible via upvotes/comments
- ✅ Diverse subreddits for signal weighting
- ✅ Good engagement indicators

**Cons:**
- ⚠️ Rate limited (~10 req/min public)
- ⚠️ Requires multiple requests for multiple subreddits
- ⚠️ Subreddit quality varies significantly
- ⚠️ Engagement scores can be gamed
- ⚠️ Mostly retail/meme discussion

---

## Data Processing Pipeline

### Architecture: Vendor Routing System

**File:** `tradingagents/dataflows/interface.py`

The framework uses a **vendor-agnostic routing system** that allows switching data sources via configuration without code changes:

```python
VENDOR_METHODS = {
    "get_news": {
        "alpha_vantage": get_alpha_vantage_news,
        "yfinance": get_news_yfinance,
    },
    "get_global_news": {
        "yfinance": get_global_news_yfinance,
        "alpha_vantage": get_alpha_vantage_global_news,
    },
    "get_insider_transactions": {
        "alpha_vantage": get_alpha_vantage_insider_transactions,
        "yfinance": get_yfinance_insider_transactions,
    },
}

def route_to_vendor(method: str, *args, **kwargs):
    """Route method calls to appropriate vendor implementation with fallback support."""
```

**Configuration (default_config.py):**
```python
"data_vendors": {
    "core_stock_apis": "yfinance",       # Default vendor
    "technical_indicators": "yfinance",
    "fundamental_data": "yfinance",
    "news_data": "yfinance",             # News defaults to yfinance
},
"tool_vendors": {
    # Tool-level config takes precedence over category-level
    # Example: "get_news": "alpha_vantage" would override category
}
```

**Vendor Switching:**
Users can override via environment variables:
```bash
TRADINGAGENTS_NEWS_DATA_VENDOR=alpha_vantage  # Switch to Alpha Vantage
TRADINGAGENTS_TOOL_VENDOR_GET_NEWS=yfinance   # Override specific tool
```

---

### Integration Point 1: Sentiment Analyst

**File:** `tradingagents/agents/analysts/sentiment_analyst.py`

**Pre-Fetching Pattern (no tool-calling):**
```python
def sentiment_analyst_node(state):
    ticker = state["company_of_interest"]
    end_date = state["trade_date"]
    start_date = _seven_days_back(end_date)
    
    # Pre-fetch all three sources BEFORE LLM invocation
    news_block = get_news.func(ticker, start_date, end_date)
    stocktwits_block = fetch_stocktwits_messages(ticker, limit=30)
    reddit_block = fetch_reddit_posts(ticker)
    
    # Inject all three into system prompt as structured blocks
    system_message = _build_system_message(
        ticker=ticker,
        start_date=start_date,
        end_date=end_date,
        news_block=news_block,
        stocktwits_block=stocktwits_block,
        reddit_block=reddit_block,
    )
    
    # Single LLM invocation (no tool-calling, no fabrication risk)
    prompt = ChatPromptTemplate.from_messages([(system_message, ...)])
    result = llm.invoke(prompt)
    
    return {"messages": [result], "sentiment_report": result.content}
```

**Key Design Decision:** Pre-fetches all data to prevent LLM fabrication
- ✅ Eliminates risk of LLM making up social media posts
- ✅ Single LLM invocation (faster, cheaper)
- ✅ Graceful degradation (all sources have fallback strings)

**Structured Data Blocks in Prompt:**
```markdown
## Data sources (pre-fetched, in this prompt)

### News headlines — Yahoo Finance, past 7 days
<start_of_news>
[news_block]
<end_of_news>

### StockTwits messages — retail-trader social platform indexed by cashtag
<start_of_stocktwits>
[stocktwits_block]
<end_of_stocktwits>

### Reddit posts — r/wallstreetbets, r/stocks, r/investing (past 7 days)
<start_of_reddit>
[reddit_block]
<end_of_reddit>
```

---

### Integration Point 2: News Analyst

**File:** `tradingagents/agents/analysts/news_analyst.py`

**Tool-Calling Pattern (on-demand fetching):**
```python
def news_analyst_node(state):
    tools = [
        get_news,          # LangChain tool wrapper
        get_global_news,
    ]
    
    system_message = (
        "You are a news researcher. Use the available tools to fetch "
        "company-specific news with get_news(ticker, start_date, end_date) "
        "and broader macroeconomic news with get_global_news(curr_date, look_back_days, limit)."
    )
    
    # LLM can decide which tools to call
    prompt = ChatPromptTemplate.from_messages([...])
    chain = prompt | llm.bind_tools(tools)
    result = chain.invoke(state["messages"])
    
    # Extract report from tool-calling result
    if len(result.tool_calls) == 0:
        report = result.content
    else:
        # Tool calls executed, extract from results
        ...
    
    return {"messages": [result], "news_report": report}
```

**Tool Wrappers:**
```python
@tool
def get_news(
    ticker: str,
    start_date: str,
    end_date: str,
) -> str:
    """Retrieve news for a given ticker."""
    return route_to_vendor("get_news", ticker, start_date, end_date)

@tool
def get_global_news(
    curr_date: str,
    look_back_days: Optional[int] = None,
    limit: Optional[int] = None,
) -> str:
    """Retrieve global/macro news."""
    return route_to_vendor("get_global_news", curr_date, look_back_days, limit)

@tool
def get_insider_transactions(ticker: str) -> str:
    """Retrieve insider transaction data."""
    return route_to_vendor("get_insider_transactions", ticker)
```

---

## Data Flow in Fundamental Analysis Pipeline

```
┌─────────────────────────────────────────────────────────────────┐
│ Input: Ticker, Trade Date                                       │
└────────────────────┬────────────────────────────────────────────┘
                     │
         ┌───────────┴───────────┐
         │                       │
    ┌────▼─────┐            ┌───▼─────┐
    │ Market   │            │Sentiment│
    │Analyst   │            │Analyst  │
    └────┬─────┘            └───┬─────┘
         │                      │
         │                      ├─► Yahoo Finance News    ────┐
         │                      ├─► StockTwits Messages   ────┤
         │                      └─► Reddit Posts          ────┤
         │                                                    │
         │      ┌────────────────────────────────────────────┘
         │      │
    ┌────▼──────▼──────┐
    │   Sentiment      │ ◄─── Pre-fetched data injected into prompt
    │   Report         │      (no tool-calling, prevents fabrication)
    └────┬─────────────┘
         │
    ┌────▼──────────────┐
    │  News Analyst     │
    │  (tool-enabled)   │
    └────┬──────────────┘
         │
         ├─► get_news(ticker, start_date, end_date)
         ├─► get_global_news(date, lookback, limit)
         └─► get_insider_transactions(ticker)
                     │
         ┌───────────┴─────────────┐
         │                         │
    ┌────▼────────┐        ┌──────▼──────┐
    │ yfinance    │        │Alpha Vantage│
    │ (default)   │        │(if config)  │
    └─────────────┘        └─────────────┘
         │                         │
         └───────────┬─────────────┘
                     │
    ┌────────────────▼──────────────────┐
    │  Formatted News Block (Markdown)  │
    │  - Title, Source, Link, Summary   │
    │  - Deduped, Date-filtered         │
    │  - Graceful fallback on error     │
    └────────────────┬──────────────────┘
         │
    ┌────▼────────────────┐
    │  Fundamentals       │
    │  Analyst reads news │
    │  + financials data  │
    └────────────────────┘
```

---

## Data Structure & Processing Details

### News Article Normalization

**Input (yfinance nested format):**
```json
{
  "content": {
    "title": "Apple's AI Push...",
    "summary": "Apple announced new AI features...",
    "provider": { "displayName": "Reuters" },
    "canonicalUrl": { "url": "https://..." },
    "clickThroughUrl": { "url": "https://..." },
    "pubDate": "2026-05-14T14:30:00Z"
  }
}
```

**Processed output:**
```python
{
    "title": "Apple's AI Push...",
    "summary": "Apple announced new AI features...",
    "publisher": "Reuters",
    "link": "https://...",
    "pub_date": datetime(2026, 5, 14, 14, 30, 0, tzinfo=UTC)
}
```

**Rendered markdown:**
```markdown
### Apple's AI Push... (source: Reuters)
Apple announced new AI features...
Link: https://...
```

### Error Handling Pattern

All fetchers follow the same graceful degradation pattern:

```python
try:
    data = fetch_data(ticker)
    if not data:
        return f"No data found for {ticker}"
    return format_data(data)
except Exception as e:
    logger.warning(f"Fetch failed: {e}")
    return f"<unavailable: {type(e).__name__}>"  # No exception raised
```

**Benefit:** LLM always receives a string result, no special-case handling needed.

---

## Configuration Reference

### Default Configuration

**File:** `tradingagents/default_config.py`

```python
DEFAULT_CONFIG = {
    # News parameters
    "news_article_limit": 100,              # Max articles per ticker
    "global_news_lookback_days": 7,         # Days to search back
    "global_news_article_limit": 50,        # Max global articles
    
    # Search queries for global news (yfinance Search)
    "global_news_queries": [
        "Federal Reserve interest rates inflation",
        "S&P 500 earnings GDP economic outlook",
        "geopolitical risk trade war sanctions",
        "ECB Bank of England BOJ central bank policy",
        "oil commodities supply chain energy",
    ],
    
    # Data vendor configuration
    "data_vendors": {
        "core_stock_apis": "yfinance",      # Options: alpha_vantage, yfinance
        "technical_indicators": "yfinance",
        "fundamental_data": "yfinance",
        "news_data": "yfinance",            # Default: yfinance (free)
    },
    
    "tool_vendors": {
        # Tool-level config (overrides category level)
        # Example: "get_news": "alpha_vantage"
    },
}
```

### Environment Variable Overrides

```bash
# Switch news data vendor to Alpha Vantage
export TRADINGAGENTS_NEWS_DATA_VENDOR=alpha_vantage

# Specific tool override
export TRADINGAGENTS_TOOL_VENDOR_GET_NEWS=yfinance

# News parameters
export TRADINGAGENTS_NEWS_ARTICLE_LIMIT=150
export TRADINGAGENTS_GLOBAL_NEWS_LOOKBACK_DAYS=14

# Alpha Vantage API key
export ALPHA_VANTAGE_API_KEY=your_key_here
```

---

## Comparison Matrix

| Feature | Yahoo Finance | Alpha Vantage | StockTwits | Reddit |
|---------|---|---|---|---|
| **Cost** | Free | 💰 Paid/Freemium | Free | Free |
| **API Key** | ❌ No | ✅ Yes | ❌ No | ❌ No |
| **Rate Limit** | ~10 req/min | 5/min (free), higher (paid) | ~10 req/min | ~10 req/min |
| **Authentication** | None | API Key | None | None |
| **Ticker News** | ✅ Yes | ✅ Yes | ❌ No | ✅ Via search |
| **Global News** | ✅ Search API | ✅ Topic-based | ❌ No | ❌ Via search |
| **Sentiment Scoring** | ❌ Manual | ✅ Built-in | ✅ User-labeled | ⚠️ Via engagement |
| **Insider Data** | ✅ Yes | ✅ Yes | ❌ No | ❌ No |
| **Crypto/Forex** | ⚠️ Limited | ✅ Full | ✅ Yes | ❌ No |
| **Data Freshness** | ~Real-time | Real-time | Real-time | Real-time |
| **Historical Depth** | ~3-5 years | ~20 years | ~7 days | ~7 days |
| **Data Quality** | ⭐⭐⭐⭐ | ⭐⭐⭐⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐ |
| **Signal Quality** | Institutional | Institutional | Retail | Retail/Meme |

---

## Key Implementation Insights

### 1. **Graceful Degradation Philosophy**

All fetchers return strings, never raise exceptions:
```python
# Bad (what they DON'T do):
if error:
    raise FetchError("...")  # ❌ Forces caller to handle

# Good (what they DO):
if error:
    return f"<data unavailable: {error}>"  # ✅ LLM sees placeholder
```

**Benefit:** LLM receives consistent string input, continues analysis with degraded signal.

---

### 2. **Pre-Fetching Before LLM (Sentiment Analyst)**

**Why pre-fetch?**
- ❌ **Problem:** LLM asked "analyze Reddit posts" but given only news → fabricates posts
- ✅ **Solution:** Pre-fetch all sources, inject into prompt as facts

**Result:** Fix for issue #557 (fabrication bug)

---

### 3. **Vendor Abstraction Layer**

Single point of truth for switching implementations:
```python
# Same call, different implementations
result = route_to_vendor("get_news", ticker, start, end)
# Routes to: get_news_yfinance() or get_alpha_vantage_news()
# based on config
```

**Benefit:** Can A/B test vendors, add new sources without changing agent code.

---

### 4. **Rate Limit Awareness**

```python
# Retry with backoff for rate limits
def yf_retry(func, max_retries=3, base_delay=2.0):
    for attempt in range(max_retries + 1):
        try:
            return func()
        except YFRateLimitError:
            if attempt < max_retries:
                delay = base_delay * (2 ** attempt)  # Exponential backoff
                sleep(delay)
            else:
                raise

# Inter-request delays for multiple sources
sleep(0.4)  # Between Reddit subreddit fetches
```

---

### 5. **Date Filtering Patterns**

**Client-side (yfinance, Reddit):**
```python
# Python code filters results after fetching
if start_date <= pub_date <= end_date:
    include_article()
```

**Server-side (Alpha Vantage):**
```python
# API parameters filter on server
params = {"time_from": "20260507T0000", "time_to": "20260514T0000"}
```

**Look-ahead guard (prevent future data):**
```python
# Reject articles published after current date
if pub_date > curr_date + timedelta(days=1):  # +1 allows same-day margin
    skip_article()
```

---

## Implementation Recommendations for Laravel Dashboard

### Immediate (Core):
1. **Start with yfinance** (free, no auth, sufficient for POC)
   - `yfinance_news.py` pattern: `Ticker.get_news()`
   - Parse nested JSON structure
   - Implement date filtering

2. **Add StockTwits** (retail sentiment signal)
   - Simple HTTP endpoint, 30 messages
   - Parse sentiment tags (Bullish/Bearish)
   - Weight by message count aggregation

3. **Add Reddit** (community discussion signal)
   - Multiple subreddit queries
   - Weight posts by engagement (upvotes + comments)
   - Extract title + excerpt

### Short-term (Enhanced):
4. **Vendor routing abstraction** (match TradingAgents pattern)
   - Configuration-driven switching
   - Easy to add Alpha Vantage later

5. **Graceful degradation**
   - Return placeholder strings on errors
   - Never crash on data fetch failure

6. **Rate limit handling**
   - Exponential backoff for retries
   - Inter-request delays between multiple sources

### Long-term (Premium):
7. **Alpha Vantage integration** (if paying for premium)
   - Built-in sentiment scoring
   - Insider transaction data
   - Global news by topic

8. **Sentiment aggregation**
   - Combine all three sources into single score
   - Weight by source reliability and recency

---

## Data Flow Diagram: Complete Pipeline

```
┌──────────────────────────────────────────────────────────────────┐
│ Ticker: AAPL | Trade Date: 2026-05-14                           │
└────────────────────────────┬─────────────────────────────────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
        ▼                    ▼                    ▼
   ┌─────────┐          ┌──────────┐        ┌─────────┐
   │ Yahoo   │          │StockTwits│        │ Reddit  │
   │ Finance │          │          │        │         │
   └────┬────┘          └────┬─────┘        └────┬────┘
        │                    │                    │
        │ ticker, date range │ ticker, limit=30  │ ticker, 3 subreddits
        │                    │                   │
        ▼                    ▼                    ▼
   ┌─────────────────┐  ┌──────────────────┐  ┌─────────────────┐
   │ 42 articles:    │  │ 30 messages:     │  │ 15 posts total: │
   │ title, summary  │  │ sentiment tags   │  │ score, comments │
   │ publisher, link │  │ (Bullish/Bearish)   │ body excerpt    │
   │ pubDate         │  │                  │  │ subreddit       │
   └────────┬────────┘  └────────┬─────────┘  └────────┬────────┘
            │                    │                     │
            └────────────────────┼─────────────────────┘
                                 │
                                 ▼
                    ┌──────────────────────────────┐
                    │ Format & Deduplicate         │
                    │ - Remove duplicates (title)  │
                    │ - Filter by date range       │
                    │ - Remove future data         │
                    │ - Truncate long text         │
                    └────────────┬─────────────────┘
                                 │
                    ┌────────────┴────────────────┐
                    │                            │
         ┌──────────▼────────────┐    ┌────────▼──────────┐
         │ Sentiment Analyst:    │    │ News Analyst:     │
         │                       │    │                   │
         │ Pre-fetch ALL data    │    │ Tool-calling mode │
         │ Inject into prompt    │    │ (on-demand fetch) │
         │ as structured blocks  │    │                   │
         │                       │    │ Optionally uses   │
         │ Single LLM call       │    │ Alpha Vantage     │
         │ (no fabrication)      │    │ for sentiment     │
         └──────────┬────────────┘    └────────┬──────────┘
                    │                         │
                    ▼                         ▼
         ┌──────────────────────┐  ┌────────────────────┐
         │ Sentiment Report:    │  │ News Report:       │
         │ - Bullish/Bearish   │  │ - Key headlines    │
         │ - Top narratives     │  │ - Macro trends     │
         │ - Source breakdown   │  │ - Insider news     │
         │ - Confidence level   │  │ - Impact analysis  │
         └──────────┬───────────┘  └────────┬───────────┘
                    │                       │
                    └───────────┬───────────┘
                                │
                    ┌───────────▼────────────┐
                    │ Fundamentals Analyst:  │
                    │                        │
                    │ Reads news + reports   │
                    │ + financial statements │
                    │ + industry context     │
                    │                        │
                    │ → Investment Analysis  │
                    └───────────┬────────────┘
                                │
                                ▼
                    ┌──────────────────────┐
                    │ FINAL FUNDAMENTAL    │
                    │ ANALYSIS REPORT      │
                    └──────────────────────┘
```

---

## Conclusion

TradingAgents implements a **sophisticated, multi-source news pipeline** optimized for:
- **Cost efficiency** (primary: free sources, optional: paid)
- **Risk mitigation** (no LLM fabrication via pre-fetching)
- **Flexibility** (vendor routing allows easy switching)
- **Reliability** (graceful degradation on failures)

**For Laravel dashboard**, start with **yfinance + StockTwits + Reddit** (free tier), then add **Alpha Vantage** when budget allows for institutional-grade sentiment scoring.

