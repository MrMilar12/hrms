<?php
/** @var array $accomplishment */
/** @var array $attachments */
/** @var array $reviews */
function printValue($value, string $fallback = '—'): string
{
    return htmlspecialchars((string) ($value ?? $fallback));
}

$description = trim((string) ($accomplishment['description'] ?? ''));
$paragraphs = $description === '' ? [] : preg_split('/\R{2,}/', $description);
$statusClass = strtolower(str_replace(' ', '-', (string) $accomplishment['status']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Accomplishment Report — <?= printValue($accomplishment['title']) ?></title>
<style>
    :root { --ink:#162033; --muted:#687286; --line:#dfe4ec; --blue:#346be7; --violet:#7c5ce5; --paper:#fff; }
    * { box-sizing:border-box; }
    body { margin:0; background:#edf1f7; color:var(--ink); font-family:"Segoe UI",Arial,sans-serif; font-size:10.5pt; line-height:1.65; }
    .page { width:210mm; min-height:297mm; margin:18px auto; padding:17mm 18mm 15mm; background:var(--paper); box-shadow:0 18px 50px rgba(24,39,75,.14); }
    .print-action { position:fixed; top:18px; right:18px; border:0; border-radius:999px; padding:10px 16px; background:linear-gradient(135deg,var(--blue),var(--violet)); color:#fff; font:600 13px "Segoe UI",Arial,sans-serif; cursor:pointer; box-shadow:0 8px 20px rgba(52,107,231,.28); }
    .brand-row { display:flex; align-items:center; justify-content:space-between; color:var(--muted); font-size:8.5pt; letter-spacing:.08em; text-transform:uppercase; }
    .brand { display:flex; align-items:center; gap:8px; font-weight:700; color:var(--ink); }
    .brand-mark { width:10px; height:10px; border-radius:50%; background:linear-gradient(135deg,var(--blue),var(--violet)); box-shadow:0 0 12px rgba(124,92,229,.45); }
    .hero { padding:13mm 0 8mm; border-bottom:2px solid var(--ink); }
    h1 { max-width:650px; margin:0 0 9px; font-size:25pt; line-height:1.18; letter-spacing:-.04em; }
    .subtitle { margin:0; color:var(--muted); font-size:11pt; }
    .status { display:inline-block; margin-top:14px; padding:4px 11px; border-radius:999px; color:#2554bb; background:#eaf0ff; font-size:8.5pt; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
    .status.approved { color:#08733e; background:#e2f7eb; } .status.returned { color:#b33c2f; background:#feeae8; } .status.for-review { color:#76520b; background:#fff4d6; }
    .facts { display:grid; grid-template-columns:repeat(3,1fr); margin:8mm 0 11mm; border:1px solid var(--line); border-radius:10px; overflow:hidden; }
    .fact { padding:12px 14px; border-right:1px solid var(--line); } .fact:last-child { border-right:0; }
    .fact-label { display:block; margin-bottom:3px; color:var(--muted); font-size:7.5pt; font-weight:700; letter-spacing:.09em; text-transform:uppercase; }
    .fact-value { display:block; font-weight:600; font-size:9.5pt; line-height:1.35; }
    .section { margin-top:10mm; } .section-heading { display:flex; align-items:center; gap:9px; margin:0 0 13px; font-size:9pt; letter-spacing:.1em; text-transform:uppercase; } .section-heading::after { content:""; height:1px; flex:1; background:var(--line); }
    .story { padding:2px 0 0 17px; border-left:3px solid var(--blue); } .story p { margin:0 0 11px; color:#273349; } .story p:last-child { margin-bottom:0; }
    .evidence-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:12px; }
    .evidence { margin:0; overflow:hidden; border:1px solid var(--line); border-radius:9px; break-inside:avoid; }
    .evidence img { display:block; width:100%; height:185px; object-fit:cover; background:#f2f4f8; }
    .evidence figcaption { padding:8px 10px; color:var(--muted); font-size:8.5pt; }
    .empty { padding:13px 15px; border:1px dashed #c7cfdb; border-radius:9px; color:var(--muted); font-style:italic; }
    .review { display:grid; grid-template-columns:104px 1fr; gap:13px; padding:10px 0; border-bottom:1px solid var(--line); break-inside:avoid; } .review:last-child { border-bottom:0; }
    .review-date { color:var(--muted); font-size:8.5pt; } .review-title { margin:0 0 2px; font-weight:700; } .review-copy { margin:0; color:#4d586b; font-style:italic; }
    .footer { display:flex; justify-content:space-between; margin-top:15mm; padding-top:6mm; border-top:1px solid var(--line); color:var(--muted); font-size:8pt; }
    @page { size:A4; margin:0; }
    @media print { body { background:#fff; } .page { width:auto; min-height:0; margin:0; padding:17mm 18mm 15mm; box-shadow:none; } .print-action { display:none; } }
    @media (max-width:700px) { .page { width:100%; margin:0; padding:28px 22px; } .facts, .evidence-grid { grid-template-columns:1fr; } .fact { border-right:0; border-bottom:1px solid var(--line); } .fact:last-child { border-bottom:0; } h1 { font-size:22pt; } }
</style>
</head>
<body>
<button class="print-action" type="button" onclick="window.print()">Print / Save PDF</button>
<article class="page">
    <div class="brand-row"><span class="brand"><span class="brand-mark"></span> HRMS</span><span>Accomplishment Report</span></div>
    <header class="hero">
        <h1><?= printValue($accomplishment['title']) ?></h1>
        <p class="subtitle">A documented record of completed work and supporting evidence.</p>
        <span class="status <?= $statusClass ?>"><?= printValue($accomplishment['status']) ?></span>
    </header>
    <section class="facts" aria-label="Accomplishment details">
        <div class="fact"><span class="fact-label">Prepared by</span><span class="fact-value"><?= printValue($accomplishment['employee_name']) ?></span></div>
        <div class="fact"><span class="fact-label">Completion date</span><span class="fact-value"><?= printValue($accomplishment['accomplishment_date']) ?></span></div>
        <div class="fact"><span class="fact-label">Related task</span><span class="fact-value"><?= printValue($accomplishment['task_title']) ?></span></div>
    </section>
    <section class="section"><h2 class="section-heading">Accomplishment story</h2><div class="story">
        <?php if ($paragraphs): ?>
            <?php foreach ($paragraphs as $paragraph): ?><p><?= nl2br(printValue(trim($paragraph), '')) ?></p><?php endforeach; ?>
        <?php else: ?><p>No written description was provided for this accomplishment.</p><?php endif; ?>
    </div></section>
    <section class="section"><h2 class="section-heading">Supporting evidence</h2>
        <?php if ($attachments): ?><div class="evidence-grid">
            <?php foreach ($attachments as $att): ?><figure class="evidence"><img src="<?= BASE_URL ?>/files/accomplishment-attachment/<?= (int) $att['id'] ?>" alt="Supporting evidence"><figcaption><?= printValue($att['caption'], 'Supporting evidence photo') ?></figcaption></figure><?php endforeach; ?>
        </div><?php else: ?><div class="empty">No evidence photos were attached to this report.</div><?php endif; ?>
    </section>
    <?php if ($reviews): ?><section class="section"><h2 class="section-heading">Review history</h2>
        <?php foreach ($reviews as $review): ?><div class="review"><div class="review-date"><?= printValue($review['reviewed_at']) ?></div><div><p class="review-title"><?= printValue($review['status']) ?> <span style="font-weight:400;color:var(--muted);">by <?= printValue($review['reviewer_username']) ?></span></p><?php if ($review['comments']): ?><p class="review-copy">“<?= nl2br(printValue($review['comments'], '')) ?>”</p><?php endif; ?></div></div><?php endforeach; ?>
    </section><?php endif; ?>
    <footer class="footer"><span>HRMS · Accomplishment &amp; Evidence</span><span>Generated <?= date('F j, Y') ?></span></footer>
</article>
</body>
</html>
