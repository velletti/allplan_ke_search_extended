function allplan_kesearch_change(e) {
    // first rename all existing selects to directory1, directory2 or directory3
    $('SELECT.kesearch-directory').each( function() {
        console.log( "init  : " + $( this).data('cat') ) ;
        $( this).attr( 'name' , $( this).data('cat') )
    });
    // then set name of  changed select box to directory as this will be submitted

  //  console.log( "Changed Element  Name : " + $( 'SELECT.kesearch-directory2' ).attr( 'name' ) ) ;
    $( e).attr( 'name' , 'tx_kesearch_pi1[directory]' )   ;
  //  console.log( "After Changed  Name : " + $( 'SELECT.kesearch-directory2' ).attr( 'name' ) ) ;

    if($( e).data('cat') =='tx_kesearch_pi1[directory1]') {
        $( 'SELECT.kesearch-directory2').remove() ;
        $( 'SELECT.kesearch-directory3').remove() ;
        allplan_kesearch_directory(2,$( e).data('pid'),$( e).data('lng'), $( e).val() )

    }
    if($( e).data('cat') =='tx_kesearch_pi1[directory2]') {
        $( 'SELECT.kesearch-directory3').remove() ;
        allplan_kesearch_directory(3,$( e).data('pid'),$( e).data('lng') , $( e).val())
    }
}


function allplan_kesearch_directory(Level,pid,lng, directory ){
    Level = parseInt(Level) ;
    $.ajax({
        type: 'GET',
        url: '/index.php',
        cache:      false,
        async: true,
        data: 'id=' + pid + '&eIDMW=kesearch&tx_kesearch_pi1[directory]=' + directory + '&tx_kesearch_pi1[level]=' + Level + '&L=' + lng,
        success: function(result) {
            if(Level == 2 ){
                $( 'SELECT.kesearch-directory1').after(result) ;
                $( 'SELECT.kesearch-directory2').on('change' , allplan_kesearch_change ) ;
              //  console.log( "Level 2 Name : " + $( 'SELECT.kesearch-directory2' ).attr( 'name' ) ) ;

            }
            if(Level == 3 ){
                $( 'SELECT.kesearch-directory2').after(result) ;
              //  console.log( "Level 3 Name : " + $( 'SELECT.kesearch-directory2' ).attr( 'name' ) ) ;
                $( 'SELECT.kesearch-directory2' ).attr( 'name' , 'tx_kesearch_pi1[directory]' )   ;
              //  console.log( "After 3 Name : " + $( 'SELECT.kesearch-directory2' ).attr( 'name' ) ) ;
            }
        }
    });
}