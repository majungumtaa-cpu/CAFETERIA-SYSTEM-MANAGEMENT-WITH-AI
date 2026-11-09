<?php
// ================================================
// chatbot_api.php — Cafeteria Assistant API
// ================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
require_once 'config.php';

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? trim($input['message']) : '';
$chat_id = isset($input['chat_id']) ? $input['chat_id'] : '';

if ($message === '') {
    echo json_encode(['reply' => 'Please type a message.']);
    exit;
}

$message_lower = strtolower($message);

// ================================================
// DETECT LANGUAGE (English default, Swahili detected)
// ================================================
$swahiliWords = ['habari', 'asante', 'tafadhali', 'karibu', 'ndio', 'hapana',
                 'chakula', 'bei', 'ngapi', 'gani', 'nini', 'wapi', 'mimi',
                 'wewe', 'sisi', 'naweza', 'nataka', 'tafuta', 'nina',
                 'je', 'una', 'mna', 'kuna', 'sana', 'vizuri', 'sawa', 'wooza'];
$isSwahili = false;
foreach ($swahiliWords as $word) {
    if (strpos($message_lower, $word) !== false) {
        $isSwahili = true;
        break;
    }
}

// ================================================
// RESPONSE HELPER
// ================================================
function reply($en, $sw, $isSwahili) {
    echo json_encode(['reply' => $isSwahili ? $sw : $en]);
    exit;
}

// ================================================
// INTENT 1: GREETING
// ================================================
if (preg_match('/(hello|hi|hey|habari|mambo|jambo|salamu|wooza)/i', $message_lower)) {
    reply(
        "Hello! 👋 I'm your Cafeteria Assistant.\n\nI can help you with:\n• Menu and prices\n• Order tracking\n• Dietary information\n• Pre-orders\n• Special requests\n\nWhat would you like to know?",
        "Habari! 👋 Mimi ni msaidizi wako wa Cafeteria.\n\nNaweza kukusaidia na:\n• Menu na bei\n• Kufuatilia oda\n• Taarifa za lishe\n• Kuagiza mapema\n• Maombi maalum\n\nUngependa kujua nini?",
        $isSwahili
    );
}

// ================================================
// INTENT 2: THANK YOU
// ================================================
if (preg_match('/(thank|asante|thanks)/i', $message_lower)) {
    reply(
        "You're welcome! 😊 Anything else I can help with?",
        "Karibu! 😊 Kuna kitu kingine ninaweza kukusaidia?",
        $isSwahili
    );
}

// ================================================
// INTENT 3: MENU / PRICE
// ================================================
if (preg_match('/(menu|price|how much|bei|ngapi|chakula|food|eat|kula)/i', $message_lower)) {
    $sql = "SELECT product_name, price, quantity FROM product WHERE quantity > 0 ORDER BY product_name LIMIT 15";
    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        reply(
            "Sorry, the menu is currently unavailable.",
            "Samahani, menu haipatikani kwa sasa.",
            $isSwahili
        );
    }

    $products = [];
    $specificProduct = null;
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
        if (stripos($message_lower, strtolower($row['product_name'])) !== false) {
            $specificProduct = $row;
        }
    }

    if ($specificProduct) {
        $name = $specificProduct['product_name'];
        $price = $specificProduct['price'];
        $qty = $specificProduct['quantity'];
        reply(
            "🍽️ {$name}\nPrice: TZS {$price}\nAvailable: {$qty} units",
            "🍽️ {$name}\nBei: TZS {$price}\nInapatikana: {$qty}",
            $isSwahili
        );
    }

    $reply_en = "📋 Today's Menu:\n\n";
    $reply_sw = "📋 Menu ya Leo:\n\n";
    foreach (array_slice($products, 0, 10) as $p) {
        $line = "• {$p['product_name']} — TZS {$p['price']}\n";
        $reply_en .= $line;
        $reply_sw .= $line;
    }
    $reply_en .= "\nAsk me about a specific dish!";
    $reply_sw .= "\nNiulize kuhusu chakula maalum!";

    reply($reply_en, $reply_sw, $isSwahili);
}

// ================================================
// INTENT 4: EVENTS
// ================================================
if (preg_match('/(event|tukio|sherehe|party|christmas|celebration)/i', $message_lower)) {
    $sql = "SELECT event_name, event_date, description FROM event ORDER BY event_date LIMIT 5";
    $result = mysqli_query($conn, $sql);

    if (!$result || mysqli_num_rows($result) == 0) {
        reply("No upcoming events at the moment.", "Hakuna events kwa sasa.", $isSwahili);
    }

    $reply_en = "🎉 Upcoming Events:\n\n";
    $reply_sw = "🎉 Events Zinazokuja:\n\n";
    while ($row = mysqli_fetch_assoc($result)) {
        $line = "• {$row['event_name']} — {$row['event_date']}\n";
        if ($row['description']) $line .= "  {$row['description']}\n";
        $reply_en .= $line;
        $reply_sw .= $line;
    }
    reply($reply_en, $reply_sw, $isSwahili);
}

// ================================================
// INTENT 5: PAYMENT METHODS
// ================================================
if (preg_match('/(payment|pay|lipa|malipo|pesa|money|mpesa|m-pesa|tigo|airtel)/i', $message_lower)) {
    $sql = "SELECT DISTINCT method_name FROM payment_method WHERE method_name IS NOT NULL AND method_name != '' ORDER BY method_name LIMIT 10";
    $result = mysqli_query($conn, $sql);

    $methods = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $methods[] = $row['method_name'];
        }
    }

    if (empty($methods)) {
        reply("Payment information is currently unavailable.", "Taarifa za malipo hazipatikani kwa sasa.", $isSwahili);
    }

    $reply_en = "💳 Accepted Payment Methods:\n\n";
    $reply_sw = "💳 Njia za Malipo:\n\n";
    foreach ($methods as $m) {
        $reply_en .= "• {$m}\n";
        $reply_sw .= "• {$m}\n";
    }
    reply($reply_en, $reply_sw, $isSwahili);
}

// ================================================
// INTENT 6: ORDER TRACKING
// ================================================
if (preg_match('/(order|oda|tracking|track|status|my order)/i', $message_lower)) {
    reply(
        "📦 To track your order, please log in to your Customer Portal. You'll see the status of all your orders there (pending, preparing, ready, or completed).",
        "📦 Kufuatilia oda yako, tafadhali ingia kwenye Customer Portal yako. Utaona hali ya oda zako zote hapo.",
        $isSwahili
    );
}

// ================================================
// INTENT 7: DIETARY INFO
// ================================================
if (preg_match('/(dietary|diet|halal|vegetarian|vegan|gluten|allergy|lishe)/i', $message_lower)) {
    reply(
        "🥗 We offer these dietary options:\n• Halal\n• Vegetarian\n• Vegan\n• Gluten-Free\n• Dairy-Free\n• Nut-Free\n\nTell me which one you're interested in!",
        "🥗 Tunatoa aina hizi za lishe:\n• Halal\n• Vegetarian\n• Vegan\n• Bila Gluten\n• Bila Maziwa\n• Bila Karanga\n\nNiambie unayopenda!",
        $isSwahili
    );
}

// ================================================
// INTENT 8: PRE-ORDER
// ================================================
if (preg_match('/(pre.?order|book|reserve|reservation|kuagiza mapema|agiza mapema)/i', $message_lower)) {
    reply(
        "📅 Yes, we accept pre-orders! Steps:\n1. Log in to your account\n2. Choose items\n3. Set pickup time\n4. Confirm your order\n\nYour food will be ready when you arrive!",
        "📅 Ndiyo, tunapokea oda za mapema!\n1. Ingia kwenye akaunti yako\n2. Chagua vyakula\n3. Weka muda wa kuchukua\n4. Thibitisha oda\n\nChakula chako kitakuwa tayari!",
        $isSwahili
    );
}

// ================================================
// INTENT 9: SPECIAL REQUESTS
// ================================================
if (preg_match('/(special|request|maombi|ombi|custom|suggestion|complain|lalamiko)/i', $message_lower)) {
    reply(
        "✨ We welcome special requests! You can:\n• Add a note during checkout\n• Contact our staff directly\n• Use the Feedback section\n\nYour request will be sent to our team.",
        "✨ Tunapokea maombi maalum! Unaweza:\n• Kuongeza maelezo wakati wa malipo\n• Kuwasiliana na wafanyakazi\n• Kutumia sehemu ya Maoni\n\nOmbi lako litatumwa kwa timu yetu.",
        $isSwahili
    );
}

// ================================================
// INTENT 10: CONTACT / LOCATION
// ================================================
if (preg_match('/(contact|location|where|address|phone|wapi|mahali|anwani|simu)/i', $message_lower)) {
    reply(
        "📍 Contact Information:\n• Location: Our campus cafeteria\n• Hours: 7:00 AM – 9:00 PM daily\n• Email: support@cafeteria.com\n\nCome visit us!",
        "📍 Taarifa za Mawasiliano:\n• Mahali: Cafeteria yetu\n• Saa: 7:00 asubuhi – 9:00 jioni\n• Barua pepe: support@cafeteria.com\n\nKaribu!",
        $isSwahili
    );
}

// ================================================
// FALLBACK
// ================================================
reply(
    "I'm not sure I understood that. 🤔\n\nTry asking:\n• \"Show me the menu\"\n• \"Where is my order?\"\n• \"Do you have halal food?\"\n• \"Can I pre-order?\"\n• \"I have a request\"\n• \"Where are you located?\"",
    "Sijaelewa vizuri. 🤔\n\nJaribu kuuliza:\n• \"Nionyeshe menu\"\n• \"Oda yangu iko wapi?\"\n• \"Mna vyakula vya halal?\"\n• \"Naweza kuagiza mapema?\"\n• \"Nina ombi\"\n• \"Mko wapi?\"",
    $isSwahili
);