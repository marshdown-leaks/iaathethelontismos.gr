

 <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
  <script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
  <style>
  body{background:#f5f5f5;color:#302e29;} 
  </style> 

<?php
header('Content-Type: text/html; charset=UTF-8');
$cd=$_GET['cd'];

$json_string = file_get_contents("http://iaathethelontismos.gr:7001/VolunteerRestService/resources/restwebservice/transactions/".$cd);
$array = json_decode($json_string,true);



//print_r($array); die();
?>
<div class="container">
  <h2>Title</h2>
  <div class="table-responsive">   
<table class="table">
    <thead>
    <tr>
    <th>cdRs</th>
    <th>compCd</th>
    <th>compName</th>
    <th>itemDscr</th>
    <th>rsDscr</th>
    <th>tranDt</th>
    
    
    </tr>
    
    
    </thead>
  
    <?php foreach($array['transactions'] as $key => $value): ?>
   		  <tbody>
         <tr  >
            <td><?php echo $value['cdRs']; ?></td>
             <td><?php echo $value['compCd']; ?></td>
              <td><?php echo $value['compName']; ?></td>
              <td><?php echo $value['itemDscr']; ?></td>
              <td><?php echo $value['rsDscr']; ?></td>
              <td><?php echo $value['tranDt']; ?></td>
            
        </tr>
        
    <?php endforeach; ?>
    </tbody>
</table>
</div>
</div>