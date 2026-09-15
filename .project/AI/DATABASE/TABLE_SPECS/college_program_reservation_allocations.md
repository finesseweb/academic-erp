# college_program_reservation_allocations

Quota allocations inside one Reservation plan.

## Vertical
`sum(active VERTICAL seats) <= plan.basis_capacity`

Derived Open/Unreserved:
`basis_capacity - vertical total`

## Horizontal
Horizontal allocations overlay the physical bucket.
Each horizontal allocation must be `<= basis_capacity`.
Horizontal totals do not reduce Open/Unreserved and do not create extra physical seats.

One category may occur only once per plan.
