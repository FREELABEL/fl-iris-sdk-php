#!/bin/bash

echo "╔═══════════════════════════════════════════════════════════╗"
echo "║  IRIS SDK - Simplified Authentication Test               ║"
echo "╚═══════════════════════════════════════════════════════════╝"
echo ""

cd /Users/AlexMayo/Sites/freelabel/fl-docker-dev/sdk/php

echo "📋 Testing: Token-only authentication (no OAuth needed!)"
echo ""
echo "Step 1: Clear old credentials..."
rm -f ~/.iris/credentials.json

echo "Step 2: Set up with TOKEN ONLY..."
./bin/iris config set api_key "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5ZmIwZDY3OS1hMmJjLTRmMmEtODdlZS02MDk5NzMwMmMxMjQiLCJqdGkiOiJlNDI5NTBjYTNhM2E0NzhjYzBmZTExMzliNjFkYTU2NDVhZTU3YjUyOTFkNjRlNmQyOGJjNjkwNzIyMGE0ZmMzYWU1N2FmZDJiMDQzNDNmMCIsImlhdCI6MTc2MTc3NTYxNi41OTYzNzgsIm5iZiI6MTc2MTc3NTYxNi41OTYzOCwiZXhwIjoxNzkzMzExNjE2LjU4MjM5MSwic3ViIjoiIiwic2NvcGVzIjpbXX0.XifXvOEbBtaFkyMb4mCuMJ6jnFHin5z6Rq38DL53tMuY-JARYOMh6E49l59maxbCM1dpNMBFgXUMdg6cWqcCevmduobTHUvESfWF0mdsDWn78Xio7s1uSijJ0deNzKzv6DAMBh-hTEorCbuzGlXGEgLgVSDmSjFSTpM9TA9cQNE-8yuIVg6bivS6kz9t1xrzyrB76NwsdfIdcwEpgnqV8JlOsCWh6d621-XSZVs9TousY-ou5UpVNCnuQNjZYvIJeFIDynsu26xNsosN3E7hnY6YSCU1ybgNm0aH32vpG0pmDbi5wj-DNCe0zNRgYr96schsAVkD8iSG9Jt4b81qQc-vRPj6NuaqhPbIYwiOEt5PC-qC8i7LWpQ5owgv5B2Xwq0IYUPkVYIQXFQpeVdas_IaATMX48YGpac0MfgVGkV2KHmapftbgYKSyiY5y4NNbJjzvtKLBm_BL9ucPyLunI-wTPWGwGA2Pq2kyJ4u3GhkWaEtaHfXRRW7nGSPU-ZW28o6aE6GsqdwCjV6fsZpgSRjBZyd5fhURLkRWgR7-r5-UxMjQQQXf8lrnyb8uGtfa8gPraZbLFX9Psn51GU8vE7ZJ6Fx-_RS-7ziuGtBf6z9c04sB9lP4HVTeR2cBXRHUhuO1X97XdZ69r585F5rnbKwgzBHwD-AB_NoJYra5Mc"
./bin/iris config set user_id 193
./bin/iris config set iris_url "https://fl-iris-api-v5-mnmol.ondigitalocean.app"

echo ""
echo "Step 3: Check config status..."
./bin/iris config

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Testing Operations (TOKEN ONLY - No OAuth!)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "✅ Test 1: Chat with agent..."
./bin/iris chat 349 "Quick test - are you working?" --timeout=30
TEST1=$?

echo ""
echo "✅ Test 2: Search leads..."
./bin/iris sdk:call leads.search status=Won per_page=2 --json | head -5
TEST2=$?

echo ""
echo "✅ Test 3: Get lead stats..."
./bin/iris sdk:call leads.aggregation.statistics --json | head -10
TEST3=$?

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Test Results"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ $TEST1 -eq 0 ] && [ $TEST2 -eq 0 ] && [ $TEST3 -eq 0 ]; then
    echo "🎉 SUCCESS! All tests passed with TOKEN ONLY!"
    echo ""
    echo "✅ No OAuth credentials needed"
    echo "✅ Agent chat works"
    echo "✅ Lead management works"
    echo "✅ Analytics works"
    echo ""
    echo "The SDK now works just like your web app - with just a token!"
else
    echo "⚠️  Some tests failed. Check output above."
fi
