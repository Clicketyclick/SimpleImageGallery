/**
 *   @file       display.js
 *   @brief      Keybord events and Navigation
 *   @details    Handling: Up, back, next
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-21T09:07:11 / ErBa
 *   @version    2024-11-21T09:07:11
 */

// Default home dir
home='.';

//>>> Keybord events
	document.onkeydown = function (e) { 
		e = e || window.event; 
		var charCode = e.charCode || e.keyCode, 
			character = String.fromCharCode(charCode); 

	  console.log(character+"_"+charCode);
	  
	  switch(charCode) {
			case 36:// Home
				// Home
				if (typeof(close_image) === typeof(Function) )
                    close_image( home );
				break;
			case 37:// Left / Previous
				if (typeof(goto_image) === typeof(Function) )
					goto_image( path, prev );
				break;
			case 38:// Up / Close
				// Close
				if (typeof(close_image) === typeof(Function) )
					close_image( path );
				break;
			case 39:// Right / Next
				if (typeof(goto_image) === typeof(Function) )
					goto_image( path, next );
				break;
			default:
				// code block
                console.log(charCode);
		}
	  
	};
//<<< Keybord events

//----------------------------------------------------------------------
/**
 *   @brief      Close image and retur to directory
 *   
 *   @param [in]	path	Path to images
 *   
 *   @code
if (typeof(close_image) === typeof(Function) )
	close_image( home );
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @since      2024-11-21T09:13:38
 */
function close_image( path ) { 
    console.log( "?path="+path ); 
    document.location.href = "?path="+path;
}   // close_image()

//----------------------------------------------------------------------

/**
 *   @brief      Go to image
 *   
 *   @param [in]	path	Path to images
 *   @param [in]	img		Image name
 *   
 *   
 *   @code
 *   if (typeof(goto_image) === typeof(Function) )
 *   	goto_image( path, prev );
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @since      2024-11-21T09:13:44
 */
function goto_image( path, img ) { 
    console.log( "?path="+path+"&show="+img ); 
    document.location.href = "?path="+path+"&show="+img;
}   // goto_image()

slideshowSec=0;

function myStopFunction() {
  clearTimeout( slideTimeout);
}
function slideshow( active, min, loop )
{
	if ( undefined  === loop ) {
		loop = false;
	}
	delay	= 1000;

	if ( ! next )
		if ( loop )
			next	= first;

	if(active)
	{
		if( slideshowSec >= min )
		{
			console.log( "Next image "+ next + " path: "+path );
			goto_image( path + "&slide="+min , next );
			slideshowSec=0;
		}
		else
		{
			/*
			// https://stackoverflow.com/a/6220819
			Number.padLeft = (nr, len = 2, padChr = `0`) => 
			`${nr < 0 ? `-` : ``}${`${Math.abs(nr)}`.padStart(len, padChr)}`;

			
			const str1 = '5';

console.log(str1.padStart(2, '0'));
// Expected output: "05"

const fullNumber = '2034399002125581';
const last4Digits = fullNumber.slice(-4);
const maskedNumber = last4Digits.padStart(fullNumber.length, '*');

console.log(maskedNumber);
// Expected output: "************5581"

/*
console.log(Number.padLeft(3));
console.log(Number.padLeft(284, 5));
console.log(Number.padLeft(-32, 12));
console.log(Number.padLeft(-0)); // Note: -0 is not < 0
*/			
			
			//document.getElementById('slide_id').innerHTML	= min - slideshowSec;// + " " + loop;
			//document.getElementById('slide_id').innerHTML	= Number.padLeft(min - slideshowSec) ;// + " " + loop;
			counter	= min - slideshowSec;
			counter	= counter.toString()
			document.getElementById('slide_id').innerHTML	= counter.padStart(2, '0');// + " " + loop;
			//document.getElementById('slide_id').innerHTML	= str;// + " " + loop;
		}
		slideshowSec++;
		console.log( "slideshowSec1: "+ slideshowSec + " min "+ min);
		
		const slideTimeout = setTimeout('slideshow(true, '+min+');',delay);
		//console.log( "slideshowSec2: "+ slideshowSec );
	}
	else
	{
		slideshowSec=0;
	}
}




// Finished loading
console.log( window.location.pathname.substring(window.location.pathname.lastIndexOf('/') + 1) + " loaded");

//** EOF **