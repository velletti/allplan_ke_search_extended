function allplan_kesearch_change(e){$("SELECT.kesearch-directory").each(function(){$(this).attr("name",$(this).data("cat"))}),$(e).attr("name","tx_kesearch_pi1[directory]"),"tx_kesearch_pi1[directory1]"==$(e).data("cat")&&($("SELECT.kesearch-directory2").remove(),$("SELECT.kesearch-directory3").remove(),allplan_kesearch_directory(2,$(e).data("pid"),$(e).data("lng"),$(e).val())),"tx_kesearch_pi1[directory2]"==$(e).data("cat")&&($("SELECT.kesearch-directory3").remove(),allplan_kesearch_directory(3,$(e).data("pid"),$(e).data("lng"),$(e).val()))}function allplan_kesearch_directory(Level,pid,lng,directory){Level=parseInt(Level),$.ajax({type:"GET",url:"/index.php",cache:!1,async:!0,data:"id="+pid+"&eIDMW=kesearch&tx_kesearch_pi1[directory]="+directory+"&tx_kesearch_pi1[level]="+Level+"&L="+lng,success:function(result){2==Level&&($("SELECT.kesearch-directory1").after(result),$("SELECT.kesearch-directory2").on("change",allplan_kesearch_change)),3==Level&&($("SELECT.kesearch-directory2").after(result),$("SELECT.kesearch-directory2").attr("name","tx_kesearch_pi1[directory]"))}})}