<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$rss_url = "https://news.google.com/rss/search?q=football+soccer&hl=en-US&gl=US&ceid=US:en";
$rss = simplexml_load_file($rss_url);

if ($rss) {
    $count = 0;
    foreach ($rss->channel->item as $item) {
        if ($count >= 10) break;

        $title = (string)$item->title;
        $link = (string)$item->link;
        $pubDate = (string)$item->pubDate;
        $description = (string)$item->description;
        $source = (string)$item->source;

        // Featured image from description or use placeholder
        $image_url = "https://images.unsplash.com/photo-1574629810360-7efbbe195018?auto=format&fit=crop&q=80&w=800";

        // Improved content extraction for "Full Story"
        $content = strip_tags($description);
        if (strlen($content) < 100) {
            $content = "Breaking Sports Update: " . $title . ". " . $content . " Detailed analysis and tactical deep-dives on this matchup are available for our premium members. Stay tuned for more live updates and expert forecasts from the field.";
        }
        $content .= "\n\nPublished on " . $pubDate;
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $published_at = date('Y-m-d H:i:s', strtotime($pubDate));

        $stmt = $conn->prepare("INSERT IGNORE INTO news (title, slug, content, image_url, source, published_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $title, $slug, $content, $image_url, $source, $published_at);
        $stmt->execute();

        $count++;
    }
    echo "News imported from Google News RSS.";
} else {
    echo "Failed to load RSS.";
}
?>
