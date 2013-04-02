<?php
// Created by @alexcroox of http://www.sarsclan.co.uk

$playerData = array('players' => array(), 'opt' => array());

// Our list of players
$playerData['players'][] 		= 'Green_Giant_Alex';
$playerData['players'][] 		= 'JawZzy';
$playerData['players'][] 		= 'saRsThePrototype';
$playerData['players'][] 		= 'Sm0k3y1';

/* In this example we want as little information to come back from the API as possible.
** Therefore we are calling the "clear" function, which means we need to manually enable
** each stat group we need. This will help keep the size of the response down, and therefore execution time.
*/
$playerData['opt']['clear']		= true;

// Data we want to be returned
$playerData['opt']['scores']	= true;
$playerData['opt']['global']	= true;
$playerData['opt']['nextranks']	= true;
$playerData['opt']['rank']		= true;
$playerData['opt']['kits']		= true;
$playerData['opt']['imgInfo']	= true;

// Convert lists to JSON ready for the curl post request
$postData						= array();
$postData['players']			= json_encode($playerData['players']);
$postData['opt']				= json_encode($playerData['opt']);

// This example hardcodes "pc" players
$c = curl_init('http://api.bf3stats.com/pc/playerlist/');
curl_setopt($c, CURLOPT_HEADER, false);
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_USERAGENT, 'BF3StatsAPI/0.1');
curl_setopt($c, CURLOPT_HTTPHEADER, array('Expect:'));
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POSTFIELDS, $postData);
$response 	= curl_exec($c);
$statusCode	= curl_getinfo($c, CURLINFO_HTTP_CODE);
curl_close($c);

// 200 means a successful call
if($statusCode == 200) 
{
	// Decode JSON Data into an array we can easily parse
	$data = json_decode($response, true);
	
	// Counter for our players array
	$i = 0;
	
	// Loop through each of our players we set above and see what data we have for them
	foreach($playerData['players'] AS $player)
	{
		// Does this player exist in the returned data?
		if(isset($data['list'][$player]))
		{
			// This checks to see if the player actually has any stats yet on the site
			if($data['list'][$player]['status'] == "data")
			{
				$stats[$i]['name'] 		= $data['list'][$player]['name'];
				$stats[$i]['rank'] 		= $data['list'][$player]['stats']['rank']['nr'];
				$stats[$i]['score'] 	= $data['list'][$player]['stats']['scores']['score'];				
				$stats[$i]['time'] 		= $data['list'][$player]['stats']['global']['time'];
				$stats[$i]['kills'] 	= $data['list'][$player]['stats']['global']['kills'];
				$stats[$i]['deaths'] 	= $data['list'][$player]['stats']['global']['deaths'];
				$stats[$i]['skill'] 	= $data['list'][$player]['stats']['global']['elo'];
				
				$stats[$i]['kits'] 		= array();
				
				$stats[$i]['kits'][] = array('name' => 'assault', 	'time' => $data['list'][$player]['stats']['kits']['assault']['time']);
				$stats[$i]['kits'][] = array('name' => 'engineer', 	'time' => $data['list'][$player]['stats']['kits']['engineer']['time']);
				$stats[$i]['kits'][] = array('name' => 'recon', 	'time' => $data['list'][$player]['stats']['kits']['recon']['time']);
				$stats[$i]['kits'][] = array('name' => 'support', 	'time' => $data['list'][$player]['stats']['kits']['support']['time']);
				
				// Work out which is the most used kit
				usort($stats[$i]['kits'], 'sortKits');
				
				$stats[$i]['class'] 	= $stats[$i]['kits'][0]['name'];
				
				$i++;
			}
		}
	}
	
	// Order players based on score
	usort($stats, 'sortPlayers');
?>
	<!-- Lets output our players table -->
	<table>
	    <thead>
			<tr>
				<th>#</th>
				<th>Player</th>
				<th>Rank</th>
				<th>Score</th>
				<th>K/D</th>        
				<th>Ratio</th>
				<th>Skill</th>
				<th>Time</th>
			</tr>
	    </thead>
	
	    <tbody>
	    <?
		for($out = 0; $out < count($stats); $out++):
			$pos = $out + 1; ?>
			
		        <tr>
		        	<td class="first"><?=$pos?></td>
		            <td class="soldier">
						<img src="/images/kits/<?=$stats[$out]['class']?>.png" alt="" />
						<a title="click to view full stats" href="http://bf3stats.com/stats_pc/<?=$stats[$out]['name']?>" target="_blank" rel="nofollow">
							<?=$stats[$out]['name']?>
						</a>
					</td>
		            <td align="center">
		            	<img src="http://files.bf3stats.com/img/bf3/rankstiny/r<?=$stats[$out]['rank']?>.png" alt="<?=$stats[$out]['rank']?>" />
					</td>
		            <td><?=number_format($stats[$out]['score'])?></td>
		            <td><?=number_format($stats[$out]['kills'])?> / <?=number_format($stats[$out]['deaths'])?></td>
		            <td><?=round($stats[$out]['kills']/$stats[$out]['deaths'], 2)?></td>  
		            <td><?=round($stats[$out]['skill'])?></td>  
		            <td><?=sec2hms($stats[$out]['time'])?>h</td>      
		        </tr>	    
		<?
		endfor; ?>
				
	    </tbody>
	</table>
	
	<?
	// If you add ?debug=1 to the end of your script URL in the browser you can see what data is returned
	if(isset($_GET['debug']))
	{
		echo '<pre>';
			print_r($data);
		echo '</pre>';
	}	
} 
else 
{
	echo 'Error contacting API status code: '.$statusCode;
}

// Lets dump our functions down here, these would be better in a seperate include though

function sortKits($x, $y)
{
	if($x['time'] == $y['time'])
	{
		return 0;
	}
	elseif($x['time'] < $y['time'])
	{
		return 1;
	}
	else
	{
		return -1;
	}
}

function sortPlayers($x, $y)
{
	if($x['score'] == $y['score'])
	{
		return 0;
	}
	elseif($x['score'] < $y['score'])
	{
		return 1;
	}
	else
	{
		return -1;
	}
}

// Convert seconds to hours
function sec2hms($sec, $padHours = false) 
{
	$hms 	= "";
	$hours 	= intval(intval($sec) / 3600); 
	$hms 	.= ($padHours)? str_pad($hours, 2, "0", STR_PAD_LEFT). ':' : $hours;	
	return $hms;
}
?>