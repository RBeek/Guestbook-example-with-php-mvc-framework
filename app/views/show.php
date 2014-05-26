<h1 style="font-size:400%; color:red; font-family:'Palatino Linotype', 'Book Antiqua', Palatino, serif;">Gastenboek</h1>

<div class="row">
<?php 
foreach($guestbook as $entry){
?>

<div class="col-sm-12">
<div class="brdr box-shad col-sm-6 guestbook-entry">
<h4 class="media-heading">
    <a href="#" class="name clr-green" target="_parent"><?php echo $entry['name']; ?><small class="datetime"><?php echo $entry['datetime']; ?></small></a>
</h4>
    <p class="clr-535353 comment"><?php echo $entry['comment']; ?>
    </p>
    <span class="fnt-smaller fnt-lighter fnt-arial">AMS</span>

  </div>
</div>

<?php } ?>

</div>