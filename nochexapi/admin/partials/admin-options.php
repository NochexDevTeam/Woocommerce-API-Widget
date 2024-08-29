<div>
    <?php
        $showSaveBtn=false;
        $htmlSubMenu='<ul id="settings-sections" class="subsubsub hide-if-no-js">'."\n";
        $htmlFormData='';
        $htmlTitle='';
        $htmlDescription='';
        $htmlBody='';
        $settingFields = $this->settings_fields(false);
        if(is_array($settingFields)){
            $lastSetting = count($settingFields);
            $qsArray=[];
            wp_parse_str( $_SERVER['QUERY_STRING'], $qsArray );
            if(!isset($qsArray['opt'])){
                $sectionId='general';
            } else {
                $sectionId=$qsArray['opt'];
            }
            foreach( $settingFields as $section => $data ) {
                $lastSetting--;
                $current='';
                if(isset($qsArray['opt'])){
                    if($qsArray['opt'] === $section){
                        $current=' current';
                    }
                } else {
                    if($section==='general'){
                        $current=' current';
                    }
                }
                if($section==='general'){
                    $href='';
                } else {
                    $href='&opt='.$section;
                }
                $htmlSubMenu .= '<li><a class="tab'.$current.'" href="' . get_admin_url() . 'admin.php?page=wc-settings&tab=checkout&section='. $this->id . $href . '">' . ucwords($section) . '</a>'; if($lastSetting > 0){$htmlSubMenu .=' | ';} $htmlSubMenu .= '</li>' . "\n";
                $display='none';
                if($section===$sectionId){
                    $display='block';
                }
                if(isset($data['fields'])){
                    if(count($data['fields'])>0){
                        if($display==='block'){
                            $showSaveBtn=true;
                        }
                        $htmlFormData .= '<table class="form-table" style="display:'.$display.';">'."\n";
                        $htmlFormData .= $this->generate_settings_html( $data['fields'], false );
                        $htmlFormData .= '</table>'."\n";
                    }
                }
            }
            if(isset($settingFields[$sectionId]['title'])){
                $htmlTitle=$settingFields[$sectionId]['title'];
            } else {
                //
            }
            if(isset($settingFields[$sectionId]['description'])){
                $htmlDescription=$settingFields[$sectionId]['description'];
            } else {
                //
            }
            if(isset($settingFields[$sectionId]['body'])){
                $htmlBody=$settingFields[$sectionId]['body'];
            } else {
                $htmlBody='';
            }
        }
        $htmlSubMenu .= '</ul>' . "\n";
        $htmlSubMenu .= '<br class="clear" />' . "\n";
        if($showSaveBtn===false){
            $htmlSubMenu .= '<style type="text/css">form#mainform p.submit {display:none;}</style>';
        }
    ?>
    <?php echo $htmlSubMenu; ?>
    <table style="width:100%;">
        <tr>
            <td style="text-align:left; padding-right:2em; width:75%;">
                <h3><?php echo $htmlTitle; ?></h3>
                <?php echo $htmlDescription; ?>
            </td>
            <td style="text-align:right; width:25%;">
                <img src="<?php echo plugin_dir_url( dirname( __DIR__ ) ).'assets/img/'.'nochexapilogo.png'; ?>" alt="Nochex" style="height:58px;">
            </td>
        </tr>
    </table>
    <?php echo $htmlFormData; ?>
    <?php
        if($htmlBody==='showStatus') {
            echo '<div id="statusTablesContainer">'."\n";
            echo $this->generateStatusArray( true );
            echo '</div>'."\n";
        } else if($htmlBody==='showFaqs'){
            $faqs =  $this->getFAQsArray();
        ?>	
			<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
            <style>
	        /* Custom style */
            .accordion-button::after {
                background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%23333' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill-rule='evenodd' d='M8 0a1 1 0 0 1 1 1v6h6a1 1 0 1 1 0 2H9v6a1 1 0 1 1-2 0V9H1a1 1 0 0 1 0-2h6V1a1 1 0 0 1 1-1z' clip-rule='evenodd'/%3e%3c/svg%3e");
                transform: scale(.7) !important;
            }
            .accordion-button:not(.collapsed)::after {
                background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='%23333' xmlns='http://www.w3.org/2000/svg'%3e%3cpath fill-rule='evenodd' d='M0 8a1 1 0 0 1 1-1h14a1 1 0 1 1 0 2H1a1 1 0 0 1-1-1z' clip-rule='evenodd'/%3e%3c/svg%3e");
            }
            </style>
            <div class="m-4">
                <div class="accordion" id="faqAccordion">
                    <?php foreach( $faqs AS $k => $faq ):?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?php echo $k;?>">
                            <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $k;?>"><?php echo ($k+1);?>. <?php echo $faq['question'];?></button>									
                        </h2>
                        <div id="collapse<?php echo $k;?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="card-body">
                                <p><?php echo $faq['answer'];?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
            </div>
        <?php
        }else if($htmlBody==='showLogs') {
            echo '<style type="text/css">form#mainform p.submit {display:none;}</style>';
            echo '<div id="debuggingLogsContainer">'."\n";
            echo $this->generateDebuggingLogsHTML();
            echo '</div>'."\n";
        } else {
            echo $htmlBody;
        }
        if($sectionId === 'status') {
    ?>
    <!--js-->
    <script type="text/javascript">
        jQuery(function(){
            jQuery('body').on('click', '.ncx-ajax', function(e){
                e.preventDefault();
                var action = jQuery(this).data('pl_action');
                return wp.ajax.post(action,{})
                .then(function(response) {
                    console.log(response);
                    if(response.valid==true){
                        if(response.hasOwnProperty('containerId')){
                            var el=document.getElementById(response.containerId);
                            if(response.hasOwnProperty('html')){
                                el.innerHTML=response.html;
                            }
                        }
                    }
                });
            });
            var param = {};
            jQuery('body').on('click', '.looping-ajax-call', function(e){
                e.preventDefault();
                var action = jQuery(this).data('pl_action');
                return wp.ajax.post(action, param)
                .then(function(response) {
                    console.log(response);
                    if(response.valid==true){
                        if(response.hasOwnProperty('containerId')){
                            var el=document.getElementById(response.containerId);
                            if(response.hasOwnProperty('html')){
                                el.innerHTML=response.html;
                            }
                            if(response.hasOwnProperty('nextcall')){
                                param  = {'nextpage': response.nextpage, 'paymentid': response.paymentid};
                                jQuery('.looping-ajax-call').trigger('click');
                            }
                        }
                    }
                });
            });
        });
    </script>
    <!--/js-->
    <pre style="display:block;">
    <?php
            $runTestCode = false;
    ?>
    </pre>
    
    <?php if($runTestCode === true) { ?><pre>
    <?php
        //in here for all test code before breaking admin!!
        //
        //
        //
        $fullCode = 0;
        
        if($fullCode === 1){
            
        } else {
            
        }
        
        //end code block for test
        //
        //
    ?>
    </pre><?php } ?>
    
    <?php
        }
    ?>
</div>
<br class="clear" />