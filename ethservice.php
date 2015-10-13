

  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <style>
  body{background:#f5f5f5;color:#302e29;} 
  </style> 		
<?php

header('Content-Type: text/html; charset=UTF-8');
$json_string = file_get_contents("http://iaathethelontismos.gr:7001/VolunteerRestService/resources/restwebservice/companies");
$array = json_decode($json_string,true);

?>
<div class="container">
  <h2>Title</h2>
  <div class="table-responsive">      
<table class="table">
    <thead>
    <tr>
    <th>cd</th>
    <th>isEnoria</th>
    <th>mhtrPerifereia</th>
    <th>name</th>
    
    
    
    </tr>
    
    
    </thead>
  
    <?php foreach($array['companies'] as $key => $value): ?>
   		  <tbody>
         <tr  >
            <td><?php echo $value['cd']; ?></td>
             <td><?php echo $value['isEnoria']; ?></td>
              <td><?php echo $value['mhtrPerifereia']; ?></td>
              <td><?php echo '<a  href="http://iaathethelontismos.gr/transservice.php/?cd='.$value['cd'].'">' .$value['name'].'</a>'; ?></td>
           
        </tr>
        
    <?php endforeach; ?>
    </tbody>
</table>

</div>
</div>