<?php
    $array = ["Xe", "Nhà", "Tiền", "Bài", "Test"];
    

?>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Truyện Tranh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<?php foreach($array as $item):?>
    <button class="btn btn-success mt-2">
        <?php echo $item; ?>
    </button>
<?php endforeach; ?>
