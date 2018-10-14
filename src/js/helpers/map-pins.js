  // Generate map pins :
// medium.com/welldone-software/map-pins-using-svg-path-9fdfebb74501
// The pin is defined by 4 points :
//  - A is the tip of the pin
//  - C is the center of the circle (top part of the pin)
//  - B (left) and D (right) are the two points where the circle
//    connects with the straight lines going to A
// The shape is A --> B ~~> D --> A
//   where --> is a straight line
//     and ~~> a circle arc revolving around C
// dX is the horizontal delta between A and D (and negative for B)
// dY is the vertical delta between A and B && A and D (negative for
//   both as SVG Y is growing downwards)
export function pinPath(radius, height) {
    const alpha = Math.acos(radius / (height - radius))
    const deltaX = radius * Math.sin(alpha)
    const deltaY = height * (height - radius * 2) / (height - radius)
    const Ax = 0, Ay = 0, Bx = -deltaX, By = -deltaY, Dx = deltaX, Dy = -deltaY
    // console.log('radius: %s, height: %s, alpha: %s, deltaX: %s, deltaY: %s', radius, height, alpha, deltaX, deltaY)
    return `M ${Ax},${Ay}
            L ${Bx},${By}
            A ${radius} ${radius} 1 1 1 ${Dx},${Dy}
            L ${Ax},${Ay} z`
}

export function pinViewBox(radius, height, strokeWidth = 0) {
  // strokeWidth += 1 // nice antialiased curves get out of range
  const boxWidth = radius * 2 + strokeWidth * 2
  const boxHeight = height + strokeWidth * 2
  const left = 0 - (radius + strokeWidth)
  const top = 0 - (height + strokeWidth)
  return `${left} ${top} ${boxWidth} ${boxHeight}`
}
