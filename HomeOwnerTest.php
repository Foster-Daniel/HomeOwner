<?php
class HomeOwner {
    /**
     * @var string $intitial The first letter of the homeowner's forename.
     */
    private ?string $initial = null;

    /**
     * Provide a readable version of the object so that it can be converted properly.
     * 
     * @return string A string representation of the object.
     */
    function __toString(): string {
        /**
         * @var string $forenameOrInitial If the homeowner has a forename then it is assigned
         *      to this variable so it can be concatenated into a larger string later.
         *      If no valid name exists but an initial does then that is applied.
         *      If no valid forename or initial is then an empty string is returned
         */
        $forenameOrInitial = $this->forename ?: ($this->initial ?: "");
        
        return ($this->title ?: '') . " $forenameOrInitial " . ($this->surname ?: '');
    }

    /**
     * Create a HomeOwner object.
     * 
     * @param ?string $title The title of the homeowner mapped to the object.
     * @param ?string $forename The forename of the homeowner mapped to the object.
     * @param ?string $surname The surname of the homeowner mapped to the object.
     */
    function __construct(private ?string $title = null,
                         private ?string $forename = null,
                         private ?string $surname = null) {
        // Only if an initial can be extracted is the property populated.
        if (strlen($forename) > 0) $this->initial = $forename[0];
        
        // If only the first letter is provided then it is not a name and only an initial.
        if (strlen($forename) === 1) $this->forename = null;
    }

    /**
     * Create an array of homeowners from a CSV list.
     * 
     * @param string[] $homeownerDetailsInStringFormat Create from a CSV array of homeowners,
     *      separate objects for each so that they can be categorised properly.
     * 
     * @return HomeOwner[] An Array of HomeOwners.
     */
    public static function createHomeOwnersFromStringArray(array $homeownerDetailsInStringFormat): array {
        /**
         * @var HomeOwner[] $arrayOfHomeOwners An array we will add HomeOwner objects to and then return.
         */
        $arrayOfHomeOwners = [];


        foreach ($homeownerDetailsInStringFormat as $details) {
            // This is the title of the CSV document and is not a valid entry.
            if ($details === "homeowner") continue;

            /**
             * @var HomeOwner[] $groupArray When we split single entries that contain
             *      multiple people, we want to loop over the entry and create a
             *      separate object for each homeowner. They are stored in this array
             *      and then merged with the $arrayOfHomeOwners at the end of each iteration.
             */
            $groupArray = [];

            /* Because the unaltered data is a string and homeowners are separated by characters.
               We can split them with regex so that we may easily alter the separation logic and 
               create individual objects for each homeowner. */
            foreach (preg_split('/(and|&|\+)/', $details) as $spDetails) {
                // We plan to explode the new string and having trailing whitespace is problematic.
                $spDetails = trim($spDetails);
                $spDetails = explode(" ", $spDetails);

                /* The title is always at the front of an entry, therefore we can confidently assign
                   the first textual data to the title property. */
                $title = $spDetails[0];

                /* Some entries after splitting do not have a surname, therefore we need to check
                   before we assign the object one. */
                $surname = count($spDetails) > 1 ? $spDetails[count($spDetails) - 1] : null;

                /* The same logic above applies to the forename. Any middle names after the title and before
                   the surname area considered a part of the forname. */
                $forename = count($spDetails) > 2 ? implode(" ", array_slice($spDetails, 1, -1)) : null;

                /* Some instances use the '.' character after an initial, we trim these characters off as
                   to not cause issues with the constructor that creates HomeOwner objects. */
                $forename = trim($forename, "., \n\r\t\v\x00");

                // Create a HomeOwner object and append it to the array.
                $groupArray[] = new HomeOwner($title, $forename, $surname);
            }

            /* Now that we have created the objects, we must assign surnames to couples to have lost their
               implicit surname through their partner. e.g. Mr & Mrs Smith. Mr Smith loses his surname. */
            foreach($groupArray as $i => $homeOwner) {
                // We are looking backwards in this array meaning starting at 0 will cause an outi of bounds exception.
                if ($i === 0) continue;

                // Check whether a surname needs applying and then apply it.
                if ($homeOwner->surname !== null && $groupArray[$i - 1]->surname === null) {
                    $groupArray[$i - 1]->surname = $homeOwner->surname;
                }
            }

            // Merge the homeowners into one single array.
            $arrayOfHomeOwners = array_merge($arrayOfHomeOwners, $groupArray);
        }

        return $arrayOfHomeOwners;
    }

    // Magic Methods
    function __set(string $property, string $value): void {
        $this->$property = $value;
    }
    function __get(string $property): mixed {
        return $this->$property;
    }
}

/**
 * @var Resource HOME_OWNER_CSV_FILE - The provided CSV file with all the homeowner
 *     information in it.
 */
define("HOMEOWNER_CSV_FILE", fopen("HomeOwnerList.csv", "r"));

/**
 * @var string[] ARRAY_OF_HOMEOWNERS_NAMES An array that contains all the names of the
 *      homeowners from the above resource.
 */
define("ARRAY_OF_HOMEOWNERS_NAMES", fgetcsv(HOMEOWNER_CSV_FILE));

// We no longer have a need for the file now that we have extracted the homeowners from it.
fclose(HOMEOWNER_CSV_FILE);

// Iterate over the list of homeowners and create objects out of their names.
define("HOMEOWNER_OBJECT_ARRAY", HomeOwner::createHomeOwnersFromStringArray(ARRAY_OF_HOMEOWNERS_NAMES));

// Print the array so that the user can see the HomeOwner array.
print_r(HOMEOWNER_OBJECT_ARRAY);