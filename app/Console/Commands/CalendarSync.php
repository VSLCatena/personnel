<?php

namespace App\Console\Commands;

use Spatie\GoogleCalendar\Event as GEvent;
use Spatie\GoogleCalendar\Resource;
use App\Models\Event as LEvent;
use App\Models\Location;
use Carbon\Carbon;
use RRule\RRule;
use Illuminate\Console\Command;

class CalendarSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservation:CalendarSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {


        ##
        ## Sync using ID and update timestamp
        ##
       
        #get local $array1 = array("green", "red", "blue");
        $events_local = LEvent::whereBetween('datetime_start', [Carbon::today()->startOfDay(), Carbon::today()->addMonths(1)])->get();
        if(!($events_local->isEmpty())){
            $id_local = $events_local->mapWithKeys(function ($item) {
                return  array($item->google_event_id=>$item);
            });
        } else{ $id_local=collect(); }

        #get remote $array2 = array("green", "yellow", "red");
        $events_remote = GEvent::get(Carbon::today()->startOfDay(), Carbon::today()->addMonths(1));
        $id_remote = $events_remote->mapWithKeys(function ($item) {
            return  array($item->id=>$item);
        });

        #do diff/intersect on all datasets
        $id_left=$id_local->diffKeys($id_remote); #$id_left=array_values(array_diff($id_local,$id_remote)); #$array("blue"); 
        $id_intersect=$id_local->intersectByKeys($id_remote); #$id_intersect=array_values(array_intersect($id_local,$id_remote)) # intersect $array("green",  "red"); 
        $id_right=$id_remote->diffKeys($id_local); #$id_right=array_values(array_diff($id_remote,$id_local));  #$array("yellow");

        #
        # Event exist in local db and online | if id remote==id local
        #
        if($id_intersect->count() > 0){
            $id_intersect->each(function ($item, $key) {
                #
                #   Check timestamp | if local is newer-> sync to google (update publish remoteley)
                # 
                if($item->updated_at > $item->google_updated) {
                    if($item->status != 'publish') {
                        #GEvent::find($item->id,env('GOOGLE_CALENDAR_ID_PUBLIC'))->delete(); Deletes event from Calendar
                    }
                    #
                    #  sync to google (delete remotelely)
                    # 
                    else {
                        // $event = Event::find($item->google_event_id);
                        // $event->name = $item->title;
                        // $event->save();
                    }
                }
                else {
                    # This means that the event has been updated outside of this locall application... this should perhaps update the local db
                }
            
            });
        }
        #
        # Event exist in local db only | if id local does not exist remotely
        #
        if($id_left->count() > 0){
            $id_left->each(function ($item, $key) {
            #   create (if not delete)
                if($item->status == 'publish'){
                  #create
                }
            });
        }
        #
        # Event exist in Remote only| only if made online / app and has not synced with local db
        #
        if($id_right->count() > 0){
            $id_right->each(function ($item, $key) {
               #sync to local

               #DEBUG
                    if($item->summary == "TEST"){ 
                        $this->info(print_r($item));
                    }
                $event = LEvent::create([

                    'title'                 =>  $item->summary,
                    'description'           =>  $item->description,
                    'datetime_start'        =>  Carbon::parse($item->startDateTime)->format('Y-m-d H:i:s'),
                    'datetime_end'          =>  Carbon::parse($item->endDateTime)->format('Y-m-d H:i:s'),
                    'recurring_start'       =>  null, #for parent
                    'recurring_end'         =>  null,  #for parent
                    'rrule'                 =>  $item->recurringEventId ? GEvent::find($item->recurringEventId,env('GOOGLE_CALENDAR_ID_PUBLIC'))->recurrence[0] : null,
                    'all_day'               =>  $item->isAllDayEvent(),
                    'location'              =>  $item->location, #$item.atrendees.email in location.email ? location.id : item.loctionid
                    'attendees'             =>  json_encode($item->attendees),
                    'entrypoints'           =>  json_encode($item->conferenceData['entryPoints']) ,
                    'status'                => 'published',
                    'google_calendar_id'    =>  env('GOOGLE_CALENDAR_ID_PRIVATE'), #$item->getCalendarId(),
                    'google_event_id'       =>  $item->id,
                    'google_parent_event_id'=>  $item->recurringEventId,
                    'google_updated'        =>  Carbon::parse($item->updated)->format('Y-m-d H:i:s'),
                    'google_created'        =>  Carbon::parse($item->created)->format('Y-m-d H:i:s'),
                    'guests_caninviteothers'=>  $item->guestsCanInviteOthers ? True : False,
                    'guests_canmodify'      =>  $item->guestsCanModify ? True : False,
                    'guests_canseeotherguests'=> $item->guestsCanSeeOtherGuests ? True : False,
                    'organizer_displayname' => $item->organizer->displayName,
                    'creator_displayname'   =>  $item->creator->displayName,
                    'organizer_email'       =>  $item->organizer->email,
                    'creator_email'         => $item->creator->email,
                    'htmllink'              => $item->htmlLink,
                    'updated_by'            =>  900913,
                    'committee_id'          =>  null,
                ]);
                $event->save();
            });
        } 

        $Resources = Resource::get();
        foreach($Resources as $ResourceItem) {
            $location = Location::updateOrCreate(
                ['resource_id'   => $ResourceItem['id']],
                [ 
                'name'          => $ResourceItem['name'], 
                'generatedname' => $ResourceItem['generatedname'], 
                'capacity'      => $ResourceItem['capacity'],
                'floorname'     => $ResourceItem['floorname'], 
                'floorsection'  => $ResourceItem['floorsection'],  
                'features'      => json_encode($ResourceItem['features']), 
                'email'         => $ResourceItem['email'], 

            ]);
        }


        return 0;
    }
}
