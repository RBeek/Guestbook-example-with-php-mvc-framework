
<h1 style="font-size:400%; color:red; font-family:'Palatino Linotype', 'Book Antiqua', Palatino, serif;">Gastenboek voorbeeld</h1>


<hr />

<div class="row">
    <div class="col-sm-8">

        <h4 class="page-header">Teken het Gastenboek</h4>
        <form role="form" method="POST">
            <div class="form-group float-label-control">
                <label for="">Naam</label>
                <input type="text" name="name" class="form-control" placeholder="Naam">
            </div>
            <div class="form-group float-label-control">
                <label for="">Email</label>
                <input type="email" name="email" class="form-control" placeholder="Wat is je email adres?">
            </div>
            <div class="form-group float-label-control">
                <label for="">Commentaar</label>
                <textarea class="form-control" name="comment" placeholder="Schrijf een klein bericht alstublieft" rows="1"></textarea>
            </div> 
            <div class="form-group">
                <input type="submit" class="btn btn-primary" name="addguestbook" value="Nieuw bericht">
            </div>
        </form>

    </div>

    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title">
                    Technieken
                </h3>
            </div>
            <div class="panel-body">
                <ul>
                    <li>Werkt ook met AngularJS</li>
                    <li>Werkt met Bootstrap's formulier voorbeelden</li>
                    <li>Gebruikt CSS "transitions" voor cross browser support</li>
                    <li>Gebruikt placeholders voor de invoer velden als deze leeg zijn</li>
                    <li>jQuery plugin toegevoegd</li>
                    <li>Werkt met Chrome's automatische aanvul functie</li>
                    <li>Gebruikt o.a. Symfony componenten voor een MVC backend</li>
                    <li>Werkt met MySQL en MongoDB</li>

                </ul>
            </div>
        </div>
    </div>
</div>

<table>
<tr>
<td>
<div><strong><a href="guestbook">Bekijk Gastenbook</a> </strong</div>

<?php 
 ?>
</td>
</tr>
</table>